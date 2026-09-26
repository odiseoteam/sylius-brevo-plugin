<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\AttributesApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;

final class AttributesApiTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private AttributesApi $api;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->api = new AttributesApi($this->client);
    }

    public function testItListsTheAccountAttributes(): void
    {
        $this->client->queue('GET', '/contacts/attributes', BrevoFixture::response('contact_attributes'));

        $attributes = [];
        foreach ($this->api->all(new Credentials('key')) as $attribute) {
            $attributes[$attribute->name] = $attribute;
        }

        self::assertCount(15, $attributes);

        self::assertSame(Attribute::TYPE_TEXT, $attributes['FIRSTNAME']->type);
        self::assertTrue($attributes['FIRSTNAME']->isWritable());

        self::assertSame(Attribute::CATEGORY_CATEGORY, $attributes['DOUBLE_OPT-IN']->category);
        self::assertNull($attributes['DOUBLE_OPT-IN']->type);
        self::assertSame([['value' => 1, 'label' => 'Yes'], ['value' => 2, 'label' => 'No']], $attributes['DOUBLE_OPT-IN']->enumeration);

        self::assertSame(Attribute::CATEGORY_GLOBAL, $attributes['CLICKERS']->category);
        self::assertNotNull($attributes['CLICKERS']->calculatedValue);
        self::assertFalse($attributes['CLICKERS']->isWritable());
    }

    public function testItCreatesAnAttribute(): void
    {
        $this->client->queue('POST', '/contacts/attributes/normal/TOTAL_SPENT', new BrevoResponse(201));

        $this->api->create(new Credentials('key'), Attribute::CATEGORY_NORMAL, 'total_spent', Attribute::TYPE_FLOAT);

        self::assertSame(['type' => 'float'], $this->client->lastRequest()?->json);
    }

    public function testItCreatesACategoryAttribute(): void
    {
        $this->client->queue('POST', '/contacts/attributes/category/GENDER', new BrevoResponse(201));

        $this->api->create(new Credentials('key'), Attribute::CATEGORY_CATEGORY, 'GENDER', enumeration: [
            ['value' => 1, 'label' => 'Male'],
            ['value' => 2, 'label' => 'Female'],
        ]);

        self::assertSame(['enumeration' => [['value' => 1, 'label' => 'Male'], ['value' => 2, 'label' => 'Female']]], $this->client->lastRequest()?->json);
    }

    public function testItOnlyCreatesAttributesAContactCanHold(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->api->create(new Credentials('key'), Attribute::CATEGORY_GLOBAL, 'ANYTHING');
    }
}
