<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\BatchResult;
use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Odiseo\SyliusBrevoPlugin\Client\Model\OrderData;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;

interface EcommerceApiInterface
{
    /**
     * Turns the ecommerce section on. Fails (400) once it's on; Brevo takes a few minutes before the
     * catalog endpoints answer (until then they fail with 403).
     *
     * @throws BrevoException
     */
    public function activate(Credentials $credentials): void;

    /**
     * Account-wide currency of the amounts shown in Brevo; null when not set. Fails with 403 while
     * the ecommerce section is off.
     *
     * @throws BrevoException
     */
    public function getDisplayCurrency(Credentials $credentials): ?string;

    /**
     * @param string $code ISO 4217
     *
     * @throws BrevoException
     */
    public function setDisplayCurrency(Credentials $credentials, string $code): void;

    /**
     * Creates or updates the categories, in batches of 100.
     *
     * @param list<CategoryData> $categories
     *
     * @throws BrevoException
     */
    public function saveCategories(Credentials $credentials, array $categories): BatchResult;

    /**
     * Creates or updates the products, in batches of 100.
     *
     * @param list<ProductData> $products
     *
     * @throws BrevoException
     */
    public function saveProducts(Credentials $credentials, array $products): BatchResult;

    /**
     * Creates or replaces the order. Brevo creates its contact if missing (unsubscribed).
     *
     * @throws BrevoException
     */
    public function saveOrder(Credentials $credentials, OrderData $order): void;

    /**
     * Creates or replaces the orders, in batches of 1000; Brevo processes them in the background.
     *
     * @param list<OrderData> $orders
     * @param bool $historical past orders: not counted as new events for automations
     *
     * @throws BrevoException
     */
    public function saveOrders(Credentials $credentials, array $orders, bool $historical = false): void;
}
