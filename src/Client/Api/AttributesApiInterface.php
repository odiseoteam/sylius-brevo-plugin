<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;

interface AttributesApiInterface
{
    /**
     * @return list<Attribute>
     *
     * @throws BrevoException
     */
    public function all(Credentials $credentials): array;

    /**
     * Only for "normal" and "category" attributes: those a contact can hold.
     *
     * @param list<array{value: int, label: string}> $enumeration options of a "category" attribute
     * @param list<string> $multiCategoryOptions options of a "multiple-choice" attribute
     *
     * @throws BrevoException e.g. when it already exists
     */
    public function create(
        Credentials $credentials,
        string $category,
        string $name,
        ?string $type = null,
        array $enumeration = [],
        array $multiCategoryOptions = [],
    ): void;
}
