<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Form;

use Odiseo\SyliusBrevoPlugin\Client\Api\ListsApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Form\BrevoListChoices;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

final class BrevoListChoicesTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
    }

    public function testItOffersTheAccountListsOncePerRequest(): void
    {
        $this->client->queue('GET', '/contacts/lists', BrevoFixture::response('contact_lists'));
        $choices = $this->choices(new Credentials('key'));

        self::assertSame(['Your first list (#2)' => 2], $choices->forConfiguration(new ChannelConfiguration()));
        self::assertSame(['Your first list (#2)' => 2], $choices->forConfiguration(new ChannelConfiguration()));
        self::assertCount(1, $this->client->requests());
    }

    public function testThereAreNoChoicesWithoutAKeyOrWhenBrevoFails(): void
    {
        self::assertNull($this->choices(null)->forConfiguration(new ChannelConfiguration()));
        self::assertNull($this->choices(new Credentials('key'))->forConfiguration(null));

        $this->client->queue('GET', '/contacts/lists', new BrevoResponse(401, ['code' => 'unauthorized', 'message' => 'Key not found']));
        self::assertNull($this->choices(new Credentials('key'))->forConfiguration(new ChannelConfiguration()));
    }

    private function choices(?Credentials $credentials): BrevoListChoices
    {
        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getCredentials')->willReturn($credentials);

        return new BrevoListChoices($provider, new ListsApi($this->client));
    }
}
