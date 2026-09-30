<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ArrayReader;
use Odiseo\SyliusBrevoPlugin\Client\Model\BatchResult;
use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;

final class EcommerceApi implements EcommerceApiInterface
{
    /** Brevo's max categories per batch call. */
    public const BATCH_SIZE = 100;

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
        $result = new BatchResult();
        foreach (array_chunk($categories, self::BATCH_SIZE) as $batch) {
            // Without updateEnabled, existing categories fail the call.
            $data = $this->client->request($credentials, 'POST', '/categories/batch', json: [
                'categories' => array_map(static fn (CategoryData $category): array => $category->toArray(), $batch),
                'updateEnabled' => true,
            ])->data;

            $result = $result->add(new BatchResult(ArrayReader::int($data, 'createdCount') ?? 0, ArrayReader::int($data, 'updatedCount') ?? 0));
        }

        return $result;
    }
}
