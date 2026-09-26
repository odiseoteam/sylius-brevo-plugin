<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Functional\Shop;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Tests\Odiseo\SyliusBrevoPlugin\Functional\NewsletterChannelTrait;

/** The subscription answered in place, as the shop does with JavaScript. */
final class NewsletterFormComponentTest extends WebTestCase
{
    use InteractsWithLiveComponents;
    use NewsletterChannelTrait;

    public function testItSubscribesAndClearsTheForm(): void
    {
        $component = $this->component()->submitForm(['odiseo_brevo_newsletter' => ['email' => 'rincewind@example.com']], 'subscribe');

        $html = (string) $component->render();
        self::assertStringContainsString('You are subscribed to our newsletter.', $html);
        self::assertStringNotContainsString('rincewind@example.com', $html);
        self::assertTrue($this->customer('rincewind@example.com')?->isSubscribedToNewsletter());
    }

    public function testWithDoubleOptInItAsksToConfirm(): void
    {
        $this->configuration->setDoubleOptInTemplateId(5);
        $this->entityManager->flush();

        $component = $this->component()->submitForm(['odiseo_brevo_newsletter' => ['email' => 'rincewind@example.com']], 'subscribe');

        self::assertStringContainsString('Check your inbox and confirm your subscription.', (string) $component->render());
        self::assertNull($this->customer('rincewind@example.com'));
    }

    /** Live Component answers this with the form re-rendered with its errors (422). */
    public function testAnInvalidEmailFailsValidation(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        try {
            $this->component()->submitForm(['odiseo_brevo_newsletter' => ['email' => 'not-an-email']], 'subscribe');
        } finally {
            self::assertNull($this->customer('not-an-email'));
        }
    }

    public function testBotsAreIgnored(): void
    {
        $component = $this->component()->submitForm(['odiseo_brevo_newsletter' => ['email' => 'spam@example.com', 'website' => 'https://spam.example.com']], 'subscribe');

        self::assertStringContainsString('You are subscribed to our newsletter.', (string) $component->render());
        self::assertNull($this->customer('spam@example.com'));
    }

    private function component(): TestLiveComponent
    {
        return $this->createLiveComponent('odiseo_brevo:shop:newsletter_form', client: $this->browser)->setRouteLocale('en_US');
    }
}
