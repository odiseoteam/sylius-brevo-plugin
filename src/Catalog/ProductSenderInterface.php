<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\BatchResult;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;

/** Sends products to a Brevo account, skipping those unchanged since they were last sent to it. */
interface ProductSenderInterface
{
    /**
     * @param list<ProductData> $products
     *
     * @return list<ProductData>
     */
    public function changed(Credentials $credentials, array $products): array;

    /**
     * @param list<ProductData> $products
     * @param bool $force send the unchanged ones too
     *
     * @throws BrevoException
     */
    public function send(Credentials $credentials, array $products, bool $force = false): BatchResult;
}
