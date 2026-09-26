<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Newsletter;

use Odiseo\SyliusBrevoPlugin\Newsletter\ConfirmationLink;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;

final class ConfirmationLinkTest extends TestCase
{
    /** @var array<mixed> */
    private array $parameters = [];

    public function testTheLinkCarriesAValidSignature(): void
    {
        $url = $this->link()->generate(new Channel(), 'Jane@Example.com', 'es_AR');

        self::assertStringStartsWith('https://shop.example.com/newsletter/confirm?', $url);
        self::assertSame('es_AR', $this->parameters['_locale']);
        self::assertTrue($this->link()->isValid('jane@example.com', $this->expires(), $this->token()));
    }

    public function testATamperedOrExpiredLinkIsRejected(): void
    {
        $this->link()->generate(new Channel(), 'jane@example.com', 'en_US');

        self::assertFalse($this->link()->isValid('john@example.com', $this->expires(), $this->token()));
        self::assertFalse($this->link()->isValid('jane@example.com', $this->expires() + 1, $this->token()));
        self::assertFalse($this->link('other-secret')->isValid('jane@example.com', $this->expires(), $this->token()));

        $expired = time() - 1;
        $token = hash_hmac('sha256', sprintf('newsletter|jane@example.com|%d', $expired), 'secret');
        self::assertFalse($this->link()->isValid('jane@example.com', $expired, $token));
    }

    private function link(string $secret = 'secret'): ConfirmationLink
    {
        $urlGenerator = $this->createStub(ChannelUrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(function (Channel $channel, string $route, array $parameters): string {
            $this->parameters = $parameters;

            return 'https://shop.example.com/newsletter/confirm?' . http_build_query($parameters);
        });

        return new ConfirmationLink($urlGenerator, $secret);
    }

    private function expires(): int
    {
        self::assertIsInt($this->parameters['expires'] ?? null);

        return $this->parameters['expires'];
    }

    private function token(): string
    {
        self::assertIsString($this->parameters['token'] ?? null);

        return $this->parameters['token'];
    }
}
