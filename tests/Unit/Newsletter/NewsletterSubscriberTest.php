<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Newsletter;

use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Message\BrevoMessageInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\Message\RequestNewsletterConfirmation;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterSubscriber;
use Odiseo\SyliusBrevoPlugin\Newsletter\SubscriptionResult;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Customer\Context\CustomerContextInterface;

final class NewsletterSubscriberTest extends TestCase
{
    private Customer $customer;

    private ?Customer $loggedIn = null;

    private BrevoSettings $settings;

    /** @var list<BrevoMessageInterface> */
    private array $dispatched = [];

    private int $flushes = 0;

    protected function setUp(): void
    {
        $this->customer = new Customer();
        $this->customer->setEmail('jane@example.com');
        $this->settings = new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts', 'newsletter'], newsletterListId: 12);
    }

    public function testItSubscribesRightAwayWithoutDoubleOptIn(): void
    {
        self::assertSame(SubscriptionResult::Subscribed, $this->subscribe());
        self::assertTrue($this->customer->isSubscribedToNewsletter());
        self::assertSame(1, $this->flushes);
        self::assertSame([], $this->dispatched);
    }

    public function testWithDoubleOptInItAsksBrevoToConfirmFirst(): void
    {
        $this->settings = new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts', 'newsletter'], newsletterListId: 12, doubleOptInTemplateId: 5);

        self::assertSame(SubscriptionResult::ConfirmationRequested, $this->subscribe());
        self::assertFalse($this->customer->isSubscribedToNewsletter());
        self::assertSame(0, $this->flushes);
        self::assertEquals([new RequestNewsletterConfirmation('WEB', 'jane@example.com', 'en_US')], $this->dispatched);
    }

    public function testAConfirmedEmailOrTheLoggedInCustomerSkipsTheConfirmation(): void
    {
        $this->settings = new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts', 'newsletter'], newsletterListId: 12, doubleOptInTemplateId: 5);

        self::assertSame(SubscriptionResult::Subscribed, $this->subscribe(confirmed: true));

        $this->customer->setSubscribedToNewsletter(false);
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($this->customer, 7);
        $this->loggedIn = $this->customer;
        self::assertSame(SubscriptionResult::Subscribed, $this->subscribe());
        self::assertSame([], $this->dispatched);
    }

    public function testSubscribersAreLeftAsTheyAre(): void
    {
        $this->customer->setSubscribedToNewsletter(true);
        $this->settings = new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts', 'newsletter'], newsletterListId: 12, doubleOptInTemplateId: 5);

        self::assertSame(SubscriptionResult::Subscribed, $this->subscribe());
        self::assertSame(0, $this->flushes);
        self::assertSame([], $this->dispatched);
    }

    private function subscribe(bool $confirmed = false): SubscriptionResult
    {
        $resolver = $this->createStub(CustomerResolverInterface::class);
        $resolver->method('resolve')->willReturn($this->customer);

        $customerContext = $this->createStub(CustomerContextInterface::class);
        $customerContext->method('getCustomer')->willReturnCallback(fn (): ?Customer => $this->loggedIn);

        $manager = $this->createStub(ObjectManager::class);
        $manager->method('flush')->willReturnCallback(function (): void {
            ++$this->flushes;
        });

        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getSettings')->willReturnCallback(fn (): BrevoSettings => $this->settings);

        $dispatcher = $this->createStub(BrevoMessageDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (BrevoMessageInterface $message): void {
            $this->dispatched[] = $message;
        });

        return (new NewsletterSubscriber($resolver, $customerContext, $manager, $provider, $dispatcher))
            ->subscribe('jane@example.com', new Channel(), 'en_US', $confirmed);
    }
}
