<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Api\AttributesApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class AccountAttributes implements AccountAttributesInterface
{
    public function __construct(
        private readonly AttributesApiInterface $attributesApi,
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600,
    ) {
    }

    public function names(Credentials $credentials): array
    {
        /** @var list<string> $names */
        $names = $this->cache->get($this->key($credentials), function (ItemInterface $item) use ($credentials): array {
            $item->expiresAfter($this->ttl);

            return array_values(array_map(
                static fn (Attribute $attribute): string => $attribute->name,
                array_filter($this->attributesApi->all($credentials), static fn (Attribute $attribute): bool => $attribute->isWritable()),
            ));
        });

        return $names;
    }

    public function forget(Credentials $credentials): void
    {
        $this->cache->delete($this->key($credentials));
    }

    private function key(Credentials $credentials): string
    {
        return 'odiseo_brevo.contact_attributes.' . hash('xxh128', $credentials->apiKey);
    }
}
