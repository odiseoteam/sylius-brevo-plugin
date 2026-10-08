<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Sylius\Behat\Page\Shop\HomePageInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Webmozart\Assert\Assert;

final class TrackingContext implements Context
{
    /** @param CustomerRepositoryInterface<CustomerInterface> $customerRepository */
    public function __construct(
        private readonly Session $session,
        private readonly HomePageInterface $homePage,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    /**
     * @When I visit the store
     */
    public function iVisitTheStore(): void
    {
        $this->homePage->open();
    }

    /**
     * @Then the Brevo tracker should be loaded with the client key :clientKey
     */
    public function theTrackerShouldBeLoadedWithTheClientKey(string $clientKey): void
    {
        Assert::same($this->tracker()['client_key'] ?? null, $clientKey);
    }

    /**
     * @Then the Brevo tracker should not be loaded
     */
    public function theTrackerShouldNotBeLoaded(): void
    {
        Assert::null($this->session->getPage()->find('css', '#odiseo-brevo-tracker'));
    }

    /**
     * @Then the Brevo tracker should identify :email
     */
    public function theTrackerShouldIdentify(string $email): void
    {
        $identify = $this->tracker()['identify'] ?? null;
        Assert::isArray($identify, 'The tracker identifies no one.');
        Assert::same($identify['email_id'] ?? null, $email);
    }

    /**
     * @Then the Brevo tracker should identify :email as that customer
     */
    public function theTrackerShouldIdentifyAsThatCustomer(string $email): void
    {
        $this->theTrackerShouldIdentify($email);

        $customer = $this->customerRepository->findOneBy(['email' => $email]);
        Assert::isInstanceOf($customer, CustomerInterface::class);
        $identify = $this->tracker()['identify'] ?? null;
        Assert::isArray($identify);
        $customerId = $customer->getId();
        Assert::integer($customerId);
        Assert::same($identify['ext_id'] ?? null, (string) $customerId);
    }

    /**
     * @Then the Brevo tracker should not identify anyone
     */
    public function theTrackerShouldNotIdentifyAnyone(): void
    {
        Assert::null($this->tracker()['identify'] ?? null);
    }

    /** @return array<string, mixed> */
    private function tracker(): array
    {
        $element = $this->session->getPage()->find('css', '#odiseo-brevo-tracker');
        Assert::notNull($element, 'The Brevo tracker is not on the page.');

        /** @var array<string, mixed> $tracker */
        $tracker = json_decode($element->getHtml(), true, flags: \JSON_THROW_ON_ERROR);

        return $tracker;
    }
}
