<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactTargetResolver;
use Odiseo\SyliusBrevoPlugin\Contact\CustomerOrderStatsProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;

final class ContactTargetResolverTest extends TestCase
{
    /** @var array<string, BrevoSettings|null> */
    private array $settings = [];

    /** @var list<Channel> */
    private array $channels = [];

    private ?ChannelInterface $lastOrderChannel = null;

    protected function setUp(): void
    {
        $this->channel('US', new BrevoSettings('US', new Credentials('key-a'), modules: ['contacts']));
        $this->channel('CA', new BrevoSettings('CA', new Credentials('key-a'), modules: ['contacts'], syncingGuestContacts: false));
        $this->channel('AR', new BrevoSettings('AR', new Credentials('key-b'), modules: ['contacts'], syncingGuestContacts: false));
        $this->channel('UY', new BrevoSettings('UY', new Credentials('key-c')));
        $this->channel('CL', null);
    }

    public function testOneChannelPerAccountWithTheContactsModule(): void
    {
        self::assertSame(['US', 'AR'], $this->codes($this->resolver()->resolve($this->registeredCustomer())));
        self::assertSame(['US', 'AR'], $this->codes($this->resolver()->accounts()));
    }

    public function testTheChannelOfTheLastOrderRepresentsItsAccount(): void
    {
        $this->lastOrderChannel = $this->channels[1];

        self::assertSame(['CA', 'AR'], $this->codes($this->resolver()->resolve($this->registeredCustomer())));
    }

    public function testGuestsOnlyGoWhereGuestsAreSynced(): void
    {
        self::assertSame(['US'], $this->codes($this->resolver()->resolve(new Customer())));
    }

    private function channel(string $code, ?BrevoSettings $settings): void
    {
        $channel = new Channel();
        $channel->setCode($code);

        $this->channels[] = $channel;
        $this->settings[$code] = $settings;
    }

    private function resolver(): ContactTargetResolver
    {
        $configurations = array_map(static function (Channel $channel): ChannelConfiguration {
            $configuration = new ChannelConfiguration();
            $configuration->setChannel($channel);

            return $configuration;
        }, $this->channels);

        $repository = $this->createStub(ChannelConfigurationRepositoryInterface::class);
        $repository->method('findBy')->willReturn($configurations);

        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getSettings')->willReturnCallback(fn (BaseChannelInterface $channel): ?BrevoSettings => $this->settings[(string) $channel->getCode()]);

        $stats = $this->createStub(CustomerOrderStatsProviderInterface::class);
        $stats->method('getLastOrderChannel')->willReturnCallback(fn (): ?ChannelInterface => $this->lastOrderChannel);

        return new ContactTargetResolver($repository, $provider, $stats);
    }

    private function registeredCustomer(): Customer
    {
        $customer = new Customer();
        $customer->setUser(new ShopUser());

        return $customer;
    }

    /**
     * @param list<ChannelInterface> $channels
     *
     * @return list<string|null>
     */
    private function codes(array $channels): array
    {
        return array_map(static fn (ChannelInterface $channel): ?string => $channel->getCode(), $channels);
    }
}
