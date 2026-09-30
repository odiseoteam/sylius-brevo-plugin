<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\BatchResult;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;
use Psr\Cache\CacheItemPoolInterface;

final class ProductSender implements ProductSenderInterface
{
    public function __construct(
        private readonly EcommerceApiInterface $ecommerceApi,
        private readonly CacheItemPoolInterface $hashes,
    ) {
    }

    public function changed(Credentials $credentials, array $products): array
    {
        return array_values(array_filter(
            $products,
            fn (ProductData $product): bool => $this->hashes->getItem($this->key($credentials, $product))->get() !== self::hash($product),
        ));
    }

    public function send(Credentials $credentials, array $products, bool $force = false): BatchResult
    {
        $products = $force ? $products : $this->changed($credentials, $products);
        if ([] === $products) {
            return new BatchResult();
        }

        $result = $this->ecommerceApi->saveProducts($credentials, $products);

        foreach ($products as $product) {
            $this->hashes->saveDeferred($this->hashes->getItem($this->key($credentials, $product))->set(self::hash($product)));
        }
        $this->hashes->commit();

        return $result;
    }

    private function key(Credentials $credentials, ProductData $product): string
    {
        return hash('xxh128', $credentials->apiKey . '|' . $product->id);
    }

    private static function hash(ProductData $product): string
    {
        return hash('xxh128', serialize($product->toArray()));
    }
}
