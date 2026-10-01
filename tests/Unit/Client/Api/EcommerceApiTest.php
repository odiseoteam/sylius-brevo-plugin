<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApi;
use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Odiseo\SyliusBrevoPlugin\Client\Model\OrderData;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;

final class EcommerceApiTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private EcommerceApi $api;

    private Credentials $credentials;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->api = new EcommerceApi($this->client);
        $this->credentials = new Credentials('key');
    }

    public function testItActivatesTheEcommerceSection(): void
    {
        $this->client->queue('POST', '/ecommerce/activate', BrevoFixture::response('ecommerce_activate'));

        $this->api->activate($this->credentials);

        self::assertCount(1, $this->client->requests('POST', '/ecommerce/activate'));
    }

    public function testItReadsAndSetsTheDisplayCurrency(): void
    {
        $this->client->queue('GET', '/ecommerce/config/displayCurrency', BrevoFixture::response('ecommerce_display_currency_unset'));
        $this->client->queue('GET', '/ecommerce/config/displayCurrency', BrevoFixture::response('ecommerce_display_currency'));

        self::assertNull($this->api->getDisplayCurrency($this->credentials));
        self::assertSame('USD', $this->api->getDisplayCurrency($this->credentials));

        $this->api->setDisplayCurrency($this->credentials, 'ars');
        self::assertSame(['code' => 'ARS'], $this->client->lastRequest()?->json);
    }

    public function testItSavesCategoriesInBatchesOf100(): void
    {
        $this->client->queue('POST', '/categories/batch', new BrevoResponse(201, ['createdCount' => 100, 'updatedCount' => 0]));
        $this->client->queue('POST', '/categories/batch', BrevoFixture::response('categories_batch', 201));

        $categories = array_map(static fn (int $i): CategoryData => new CategoryData('taxon_' . $i, 'Taxon ' . $i), range(1, 101));
        $categories[100] = new CategoryData('caps', 'Caps', 'https://shop.example.com/en_US/taxons/caps');
        $result = $this->api->saveCategories($this->credentials, array_values($categories));

        self::assertSame(101, $result->created);
        self::assertSame(1, $result->updated);

        $requests = $this->client->requests('POST', '/categories/batch');
        self::assertCount(2, $requests);
        self::assertTrue($requests[1]->json['updateEnabled'] ?? null);
        self::assertSame([['id' => 'caps', 'name' => 'Caps', 'url' => 'https://shop.example.com/en_US/taxons/caps', 'isDeleted' => false]], $requests[1]->json['categories'] ?? null);
    }

    public function testADeletedCategoryKeepsItsName(): void
    {
        self::assertSame(['id' => 'caps', 'name' => 'Caps', 'isDeleted' => true], (new CategoryData('caps', 'Caps', deleted: true))->toArray());
    }

    public function testItSavesProductsWithTheirFields(): void
    {
        $this->client->queue('POST', '/products/batch', BrevoFixture::response('products_batch', 201));

        $result = $this->api->saveProducts($this->credentials, [
            new ProductData('tee_m', 'Tee', ['parentId' => 'tee', 'price' => 10.5, 'categories' => ['caps']]),
            new ProductData('old', 'Old', deleted: true),
        ]);

        self::assertSame(1, $result->created);
        self::assertSame([
            ['id' => 'tee_m', 'name' => 'Tee', 'parentId' => 'tee', 'price' => 10.5, 'categories' => ['caps'], 'isDeleted' => false],
            ['id' => 'old', 'name' => 'Old', 'isDeleted' => true],
        ], $this->client->lastRequest()?->json['products'] ?? null);
        self::assertTrue($this->client->lastRequest()?->json['updateEnabled'] ?? null);
    }

    public function testItSavesOrdersOneByOneOrInBatches(): void
    {
        $order = new OrderData('000042', 'paid', 45.5, new \DateTimeImmutable('2026-09-30 10:00:00', new \DateTimeZone('America/Argentina/Buenos_Aires')), new \DateTimeImmutable('2026-09-30T14:00:00Z'), [['productId' => 'tee_m', 'quantity' => 2, 'price' => 19.8]], ['identifiers' => ['email_id' => 'jane@example.com']]);

        $this->api->saveOrder($this->credentials, $order);
        self::assertSame([
            'id' => '000042',
            'status' => 'paid',
            'amount' => 45.5,
            'createdAt' => '2026-09-30T13:00:00Z',
            'updatedAt' => '2026-09-30T14:00:00Z',
            'products' => [['productId' => 'tee_m', 'quantity' => 2, 'price' => 19.8]],
            'identifiers' => ['email_id' => 'jane@example.com'],
        ], $this->client->lastRequest()?->json);

        $this->api->saveOrders($this->credentials, [$order, $order], historical: true);
        $batch = $this->client->requests('POST', '/orders/status/batch');
        self::assertCount(1, $batch);
        self::assertIsArray($batch[0]->json['orders'] ?? null);
        self::assertCount(2, $batch[0]->json['orders']);
        self::assertTrue($batch[0]->json['historical'] ?? null);
    }

    public function testTheEndpointsFailUntilTheSectionIsActive(): void
    {
        $this->client->queue('POST', '/categories/batch', BrevoFixture::response('ecommerce_not_activated', 403));

        $this->expectException(AuthenticationException::class);
        $this->api->saveCategories($this->credentials, [new CategoryData('caps', 'Caps')]);
    }
}
