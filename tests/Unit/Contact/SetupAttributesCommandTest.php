<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Api\AttributesApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\AccountAttributesInterface;
use Odiseo\SyliusBrevoPlugin\Contact\Command\SetupAttributesCommand;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;

final class SetupAttributesCommandTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->client->respondAlways('GET', '/contacts/attributes', BrevoFixture::response('contact_attributes'));
    }

    public function testItCreatesTheMissingAttributes(): void
    {
        $tester = new CommandTester($this->command());

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        $created = array_map(static fn ($request): string => $request->path, $this->client->requests('POST'));
        self::assertSame(['/contacts/attributes/normal/TOTAL_SPENT', '/contacts/attributes/normal/LAST_ORDER_DATE'], $created);
        self::assertSame(['type' => 'date'], $this->client->lastRequest()?->json);
        self::assertStringContainsString('FIRSTNAME', $tester->getDisplay());
    }

    public function testADryRunCreatesNothing(): void
    {
        $tester = new CommandTester($this->command());

        self::assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]));
        self::assertSame([], $this->client->requests('POST'));
        self::assertStringContainsString('to create', $tester->getDisplay());
    }

    public function testFailuresAreReported(): void
    {
        $this->client->queue('POST', '/contacts/attributes/normal/TOTAL_SPENT', new BrevoResponse(400, ['code' => 'invalid_parameter', 'message' => 'Nope']));

        self::assertSame(Command::FAILURE, (new CommandTester($this->command()))->execute([]));
    }

    private function command(): SetupAttributesCommand
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $resolver = $this->createStub(ContactTargetResolverInterface::class);
        $resolver->method('accounts')->willReturn([$channel]);

        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getSettings')->willReturn(new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts']));

        $builder = $this->createStub(ContactPayloadBuilderInterface::class);
        $builder->method('getAttributeTypes')->willReturn(['FIRSTNAME' => 'text', 'TOTAL_SPENT' => 'float', 'LAST_ORDER_DATE' => 'date']);

        return new SetupAttributesCommand($resolver, $provider, $builder, new AttributesApi($this->client), $this->createStub(AccountAttributesInterface::class));
    }
}
