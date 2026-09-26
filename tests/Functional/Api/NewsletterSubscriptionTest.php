<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Functional\NewsletterChannelTrait;

final class NewsletterSubscriptionTest extends WebTestCase
{
    use NewsletterChannelTrait;

    public function testItSubscribesTheEmail(): void
    {
        $this->post(['email' => 'rincewind@example.com']);

        self::assertResponseStatusCodeSame(202);
        self::assertTrue($this->customer('rincewind@example.com')?->isSubscribedToNewsletter());
    }

    public function testWithDoubleOptInBrevoAsksForAConfirmation(): void
    {
        $this->configuration->setDoubleOptInTemplateId(5);
        $this->entityManager->flush();

        $this->post(['email' => 'rincewind@example.com']);

        self::assertResponseStatusCodeSame(202);
        self::assertNull($this->customer('rincewind@example.com'));

        $requests = $this->client->requests('POST', '/contacts/doubleOptinConfirmation');
        self::assertCount(1, $requests);
        self::assertSame([12], $requests[0]->json['includeListIds'] ?? null);
        self::assertIsString($requests[0]->json['redirectionUrl'] ?? null);
        self::assertStringContainsString(self::HOST . '/en_US/newsletter/confirm?', $requests[0]->json['redirectionUrl']);
    }

    public function testAnInvalidEmailIsRejected(): void
    {
        $this->post(['email' => 'not-an-email']);

        self::assertResponseStatusCodeSame(422);
    }

    /** @param array<string, mixed> $body */
    private function post(array $body): void
    {
        $this->browser->request('POST', '/api/v2/shop/newsletter-subscriptions', server: [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], content: json_encode($body, \JSON_THROW_ON_ERROR));
    }
}
