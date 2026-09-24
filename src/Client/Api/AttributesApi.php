<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ArrayReader;
use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Webmozart\Assert\Assert;

final class AttributesApi implements AttributesApiInterface
{
    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function all(Credentials $credentials): array
    {
        $data = $this->client->request($credentials, 'GET', '/contacts/attributes')->data;

        return array_map(Attribute::fromArray(...), ArrayReader::arrays($data, 'attributes'));
    }

    public function create(
        Credentials $credentials,
        string $category,
        string $name,
        ?string $type = null,
        array $enumeration = [],
        array $multiCategoryOptions = [],
    ): void {
        Assert::oneOf($category, [Attribute::CATEGORY_NORMAL, Attribute::CATEGORY_CATEGORY]);

        $body = array_filter([
            'type' => $type,
            'enumeration' => $enumeration,
            'multiCategoryOptions' => $multiCategoryOptions,
        ], static fn (mixed $value): bool => null !== $value && [] !== $value);

        $path = sprintf('/contacts/attributes/%s/%s', rawurlencode($category), rawurlencode(strtoupper($name)));

        $this->client->request($credentials, 'POST', $path, json: $body);
    }
}
