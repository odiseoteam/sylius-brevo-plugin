<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Tracking\Event;

use Odiseo\SyliusBrevoPlugin\Client\Api\EventsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\EventData;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\ResolvedTrackingEvent;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventResolverInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSender;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;

final class TrackingEventSenderTest extends TestCase
{
    public function testItSendsTheResolvedEventToTheCustomersContact(): void
    {
        $eventsApi = $this->createMock(EventsApiInterface::class);
        $eventsApi->expects(self::once())->method('track')->with(
            self::isInstanceOf(Credentials::class),
            self::callback(static fn (EventData $event): bool => 'registered' === $event->name &&
                ['email_id' => 'carrot@example.com', 'ext_id' => '7'] === $event->identifiers &&
                ['first_name' => 'Carrot'] === $event->properties),
        );

        $this->sender($eventsApi, new ResolvedTrackingEvent('registered', ['first_name' => 'Carrot']))
            ->send('customer_registered', new \stdClass(), new Channel(), $this->customer('carrot@example.com'))
        ;
    }

    public function testNothingIsSentWithoutAnEventOrAnEmail(): void
    {
        $eventsApi = $this->createMock(EventsApiInterface::class);
        $eventsApi->expects(self::never())->method('track');

        $this->sender($eventsApi, null)->send('customer_registered', new \stdClass(), new Channel(), $this->customer('carrot@example.com'));
        $this->sender($eventsApi, new ResolvedTrackingEvent('registered', []))->send('customer_registered', new \stdClass(), new Channel(), $this->customer(null));
    }

    private function sender(EventsApiInterface $eventsApi, ?ResolvedTrackingEvent $event): TrackingEventSender
    {
        $configurationProvider = $this->createStub(ConfigurationProviderInterface::class);
        $configurationProvider->method('getSettings')->willReturn(new BrevoSettings('WEB', new Credentials('key'), modules: ['tracking']));

        $resolver = $this->createStub(TrackingEventResolverInterface::class);
        $resolver->method('resolve')->willReturn($event);

        return new TrackingEventSender($configurationProvider, $resolver, $eventsApi);
    }

    private function customer(?string $email): Customer
    {
        $customer = new Customer();
        $customer->setEmail($email);
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, 7);

        return $customer;
    }
}
