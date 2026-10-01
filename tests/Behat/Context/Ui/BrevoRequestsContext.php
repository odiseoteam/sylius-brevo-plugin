<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use Odiseo\SyliusBrevoPlugin\Testing\RecordedRequest;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
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
     * @Then the Brevo category :id should be named :name
     */
    public function theBrevoCategoryShouldBeNamed(string $id, string $name): void
    {
        Assert::same($this->lastCategoryWrite($id)['name'] ?? null, $name);
    }

    /**
     * @Then the Brevo category :id should link to the :slug taxon page
     */
    public function theBrevoCategoryShouldLinkToTheTaxonPage(string $id, string $slug): void
    {
        $url = $this->lastCategoryWrite($id)['url'] ?? null;
        Assert::string($url);
        Assert::endsWith($url, '/taxons/' . $slug);
    }

    /**
     * @Then the Brevo category :id should be deleted
     */
    public function theBrevoCategoryShouldBeDeleted(string $id): void
    {
        Assert::true($this->lastCategoryWrite($id)['isDeleted'] ?? null);
    }

    /**
     * @Then Brevo should not have received any category
     */
    public function brevoShouldNotHaveReceivedAnyCategory(): void
    {
        Assert::isEmpty($this->fakeBrevoHttpClient->requests('POST', '/categories/batch'));
    }

    /**
     * @Then the Brevo product :id should cost :price
     */
    public function theBrevoProductShouldCost(string $id, string $price): void
    {
        Assert::same($this->lastProductWrite($id)['price'] ?? null, (float) $price);
        Assert::false($this->lastProductWrite($id)['isDeleted'] ?? null);
    }

    /**
     * @Then the Brevo product :id should be deleted
     */
    public function theBrevoProductShouldBeDeleted(string $id): void
    {
        Assert::true($this->lastProductWrite($id)['isDeleted'] ?? null);
    }

    /**
     * @Then the Brevo order :number should be :status with an amount of :amount
     */
    public function theBrevoOrderShouldBe(string $number, string $status, string $amount): void
    {
        $order = $this->lastOrderWrite($number);
        Assert::same($order['status'] ?? null, $status);
        Assert::same($order['amount'] ?? null, (float) $amount);
    }

    /**
     * @Then the Brevo order :number should have :quantity :productId at :price
     */
    public function theBrevoOrderShouldHave(string $number, int $quantity, string $productId, string $price): void
    {
        Assert::inArray(['productId' => $productId, 'quantity' => $quantity, 'price' => (float) $price], (array) ($this->lastOrderWrite($number)['products'] ?? []));
    }

    /**
     * @Then the Brevo order :number should belong to :email
     */
    public function theBrevoOrderShouldBelongTo(string $number, string $email): void
    {
        $identifiers = $this->lastOrderWrite($number)['identifiers'] ?? null;
        Assert::isArray($identifiers);
        Assert::same($identifiers['email_id'] ?? null, $email);
    }

    /**
     * @Then Brevo Ecommerce should be activated showing amounts in :currency
     */
    public function brevoEcommerceShouldBeActivated(string $currency): void
    {
        Assert::count($this->fakeBrevoHttpClient->requests('POST', '/ecommerce/activate'), 1);

        $requests = $this->fakeBrevoHttpClient->requests('POST', '/ecommerce/config/displayCurrency');
        Assert::count($requests, 1);
        Assert::same($requests[0]->json['code'] ?? null, $currency);
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
     * @Then the Brevo contact :email should not have :attribute
     */
    public function theBrevoContactShouldNotHaveAttribute(string $email, string $attribute): void
    {
        $attributes = $this->lastContactWrite($email)->json['attributes'] ?? [];
        Assert::isArray($attributes);
        Assert::keyNotExists($attributes, $attribute);
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
     * @Then the Brevo contact :email should be in the list :listId
     */
    public function theBrevoContactShouldBeInTheList(string $email, int $listId): void
    {
        $listIds = $this->lastContactWrite($email)->json['listIds'] ?? [];
        Assert::isArray($listIds);
        Assert::inArray($listId, $listIds);
    }

    /**
     * @Then the Brevo contact :email should have left the list :listId
     */
    public function theBrevoContactShouldHaveLeftTheList(string $email, int $listId): void
    {
        $listIds = $this->lastContactWrite($email)->json['unlinkListIds'] ?? [];
        Assert::isArray($listIds);
        Assert::inArray($listId, $listIds);
    }

    /**
     * @Then Brevo should have emailed :email the template :templateId to join the list :listId
     */
    public function brevoShouldHaveEmailedTheConfirmation(string $email, int $templateId, int $listId): void
    {
        $requests = $this->fakeBrevoHttpClient->requests('POST', '/contacts/doubleOptinConfirmation');
        Assert::count($requests, 1);

        Assert::same($requests[0]->json['email'] ?? null, $email);
        Assert::same($requests[0]->json['templateId'] ?? null, $templateId);
        Assert::same($requests[0]->json['includeListIds'] ?? null, [$listId]);
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

    /** @return array<string, mixed> */
    private function lastCategoryWrite(string $id): array
    {
        foreach (array_reverse($this->fakeBrevoHttpClient->requests('POST', '/categories/batch')) as $request) {
            $categories = $request->json['categories'] ?? [];
            Assert::isArray($categories);
            foreach ($categories as $category) {
                if (is_array($category) && ($category['id'] ?? null) === $id) {
                    /** @var array<string, mixed> $found */
                    $found = $category;

                    return $found;
                }
            }
        }

        throw new \InvalidArgumentException(sprintf('Brevo received no category "%s".', $id));
    }

    /** @return array<string, mixed> */
    private function lastProductWrite(string $id): array
    {
        foreach (array_reverse($this->fakeBrevoHttpClient->requests('POST', '/products/batch')) as $request) {
            $products = $request->json['products'] ?? [];
            Assert::isArray($products);
            foreach ($products as $product) {
                if (is_array($product) && ($product['id'] ?? null) === $id) {
                    /** @var array<string, mixed> $found */
                    $found = $product;

                    return $found;
                }
            }
        }

        throw new \InvalidArgumentException(sprintf('Brevo received no product "%s".', $id));
    }

    /** @return array<string, mixed> */
    private function lastOrderWrite(string $number): array
    {
        foreach (array_reverse($this->fakeBrevoHttpClient->requests('POST', '/orders/status')) as $request) {
            if (($request->json['id'] ?? null) === ltrim($number, '#')) {
                /** @var array<string, mixed> $order */
                $order = $request->json;

                return $order;
            }
        }

        throw new \InvalidArgumentException(sprintf('Brevo received no order "%s".', $number));
    }
}
