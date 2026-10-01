<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ArrayReader;
use Odiseo\SyliusBrevoPlugin\Client\Model\BatchResult;
use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Odiseo\SyliusBrevoPlugin\Client\Model\OrderData;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;

final class EcommerceApi implements EcommerceApiInterface
{
    /** Brevo's max categories or products per batch call. */
    public const BATCH_SIZE = 100;

    /** Brevo's max orders per batch call. */
    public const ORDER_BATCH_SIZE = 1000;

    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function activate(Credentials $credentials): void
    {
        $this->client->request($credentials, 'POST', '/ecommerce/activate');
    }

    public function getDisplayCurrency(Credentials $credentials): ?string
    {
        $code = ArrayReader::string($this->client->request($credentials, 'GET', '/ecommerce/config/displayCurrency')->data, 'code');

        return null === $code || '' === $code ? null : $code;
    }

    public function setDisplayCurrency(Credentials $credentials, string $code): void
    {
        $this->client->request($credentials, 'POST', '/ecommerce/config/displayCurrency', json: ['code' => strtoupper($code)]);
    }

    public function saveCategories(Credentials $credentials, array $categories): BatchResult
    {
        return $this->saveInBatches($credentials, '/categories/batch', 'categories', array_map(static fn (CategoryData $category): array => $category->toArray(), $categories));
    }

    public function saveProducts(Credentials $credentials, array $products): BatchResult
    {
        return $this->saveInBatches($credentials, '/products/batch', 'products', array_map(static fn (ProductData $product): array => $product->toArray(), $products));
    }

    public function saveOrder(Credentials $credentials, OrderData $order): void
    {
        $this->client->request($credentials, 'POST', '/orders/status', json: $order->toArray());
    }

    public function saveOrders(Credentials $credentials, array $orders, bool $historical = false): void
    {
        foreach (array_chunk($orders, self::ORDER_BATCH_SIZE) as $batch) {
            $this->client->request($credentials, 'POST', '/orders/status/batch', json: [
                'orders' => array_map(static fn (OrderData $order): array => $order->toArray(), $batch),
                'historical' => $historical,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $items */
    private function saveInBatches(Credentials $credentials, string $path, string $key, array $items): BatchResult
    {
        $result = new BatchResult();
        foreach (array_chunk($items, self::BATCH_SIZE) as $batch) {
            // Without updateEnabled, existing items fail the call.
            $data = $this->client->request($credentials, 'POST', $path, json: [$key => $batch, 'updateEnabled' => true])->data;

            $result = $result->add(new BatchResult(ArrayReader::int($data, 'createdCount') ?? 0, ArrayReader::int($data, 'updatedCount') ?? 0));
        }

        return $result;
    }
}
