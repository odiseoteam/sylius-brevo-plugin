<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;
use Tests\Odiseo\SyliusBrevoPlugin\Double\RecordedRequest;
use Webmozart\Assert\Assert;

/** Checks what reached the fake Brevo API. */
final class BrevoRequestsContext implements Context
{
    public function __construct(
        private readonly FakeBrevoHttpClient $fakeBrevoHttpClient,
        private readonly RepositoryInterface $customerRepository,
    ) {
    }

    /**
     * @Then Brevo should have received the order
     */
    public function brevoShouldHaveReceivedTheOrder(): void
    {
        Assert::count($this->fakeBrevoHttpClient->requests('POST', '/dummy/orders'), 1);
    }

    /**
     * @Then the Brevo contact :email should have :attribute set to :value
     */
    public function theBrevoContactShouldHaveAttribute(string $email, string $attribute, string $value): void
    {
        $attributes = $this->lastContactWrite($email)->json['attributes'] ?? [];
        Assert::isArray($attributes);

        $actual = $attributes[$attribute] ?? null;
        Assert::scalar($actual, sprintf('The contact has no %s.', $attribute));
        Assert::same((string) $actual, $value);
    }

    /**
     * @Then the Brevo contact :email should be linked to its customer
     */
    public function theBrevoContactShouldBeLinkedToItsCustomer(string $email): void
    {
        Assert::same($this->lastContactWrite($email)->json['ext_id'] ?? null, $this->extIdOf($email));
    }

    /**
     * @Then the Brevo contact of the customer :email should now have the email :newEmail
     */
    public function theBrevoContactOfTheCustomerShouldHaveTheEmail(string $email, string $newEmail): void
    {
        $request = $this->fakeBrevoHttpClient->lastRequest();
        Assert::notNull($request);

        Assert::same($request->method, 'PUT');
        Assert::same($request->path, '/contacts/' . $this->extIdOf($email));
        Assert::same($request->query, ['identifierType' => 'ext_id']);
        Assert::isArray($request->json['attributes'] ?? null);
        Assert::same($request->json['attributes']['EMAIL'] ?? null, $newEmail);
    }

    /**
     * @Then Brevo should not have received the contact :email
     */
    public function brevoShouldNotHaveReceivedTheContact(string $email): void
    {
        Assert::null($this->findContactWrite($email));
    }

    private function lastContactWrite(string $email): RecordedRequest
    {
        $request = $this->findContactWrite($email);
        Assert::notNull($request, sprintf('Brevo received no contact "%s".', $email));

        return $request;
    }

    /** The last create or update that carries this email. */
    private function findContactWrite(string $email): ?RecordedRequest
    {
        $found = null;
        foreach ($this->fakeBrevoHttpClient->requests() as $request) {
            $isWrite = ('POST' === $request->method && '/contacts' === $request->path) ||
                ('PUT' === $request->method && str_starts_with($request->path, '/contacts/'));
            $attributes = $request->json['attributes'] ?? [];

            if ($isWrite && ($email === ($request->json['email'] ?? null) || (is_array($attributes) && $email === ($attributes['EMAIL'] ?? null)))) {
                $found = $request;
            }
        }

        return $found;
    }

    private function extIdOf(string $email): string
    {
        $customer = $this->customerRepository->findOneBy(['emailCanonical' => strtolower($email)]);
        Assert::isInstanceOf($customer, CustomerInterface::class);

        return ContactExtId::of($customer);
    }
}
