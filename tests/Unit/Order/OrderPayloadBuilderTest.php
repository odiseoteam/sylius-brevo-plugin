<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Order;

use Odiseo\SyliusBrevoPlugin\Ecommerce\AccountMoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatter;
use Odiseo\SyliusBrevoPlugin\Order\OrderPayloadBuilder;
use Odiseo\SyliusBrevoPlugin\Order\OrderStatusMapper;
use Odiseo\SyliusBrevoPlugin\Order\Payload\OrderDetailsProvider;
use Odiseo\SyliusBrevoPlugin\Phone\PhoneNumberNormalizerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemUnit;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\PaymentMethod;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\PromotionCoupon;
use Sylius\Component\Order\Model\Adjustment;

final class OrderPayloadBuilderTest extends TestCase
{
    public function testACompletedOrderHasItsTotalsItemsAndCustomer(): void
    {
        $data = $this->builder()->build($this->order());

        self::assertNotNull($data);
        self::assertSame([
            'id' => '000042',
            'status' => 'pending',
            'amount' => 45.0,
            'createdAt' => '2026-09-30T10:00:00Z',
            'updatedAt' => '2026-09-30T10:00:00Z',
            'products' => [['productId' => 'tee_m', 'quantity' => 2, 'price' => 20.0]],
            'identifiers' => ['email_id' => 'jane@example.com'],
            'billing' => ['address' => 'Seaside Fwy', 'city' => 'Rosario', 'countryCode' => 'AR', 'postCode' => '2000', 'paymentMethod' => 'Cash on delivery'],
            'coupons' => ['WELCOME'],
            'metaInfo' => ['currency' => 'USD', 'items_total' => 40.0, 'shipping_total' => 5.0, 'tax_total' => 0.0, 'discount_total' => 0.0],
        ], $data->toArray());
    }

    public function testAnOrderStillInCheckoutIsNotBuilt(): void
    {
        $order = $this->order();
        $order->setCheckoutCompletedAt(null);

        self::assertNull($this->builder()->build($order));
    }

    private function builder(): OrderPayloadBuilder
    {
        $moneyFormatter = $this->createStub(AccountMoneyFormatterInterface::class);
        $moneyFormatter->method('format')->willReturnCallback(static fn (int $amount, string $currencyCode): float => (new MoneyFormatter())->format($amount, $currencyCode));

        return new OrderPayloadBuilder(
            [new OrderDetailsProvider($moneyFormatter, new MoneyFormatter(), $this->createStub(PhoneNumberNormalizerInterface::class))],
            new OrderStatusMapper(),
            $moneyFormatter,
        );
    }

    private function order(): Order
    {
        $customer = new Customer();
        $customer->setEmail('jane@example.com');

        $order = new Order();
        $order->setChannel(new Channel());
        $order->setCustomer($customer);
        $order->setCurrencyCode('USD');
        $order->setNumber('000042');
        $order->setCheckoutCompletedAt(new \DateTimeImmutable('2026-09-30T10:00:00Z'));

        $variant = new ProductVariant();
        $variant->setCode('tee_m');
        $item = new OrderItem();
        $item->setVariant($variant);
        $item->setUnitPrice(2000);
        new OrderItemUnit($item);
        new OrderItemUnit($item);
        $order->addItem($item);

        $shipping = new Adjustment();
        $shipping->setType(AdjustmentInterface::SHIPPING_ADJUSTMENT);
        $shipping->setAmount(500);
        $order->addAdjustment($shipping);

        $address = new Address();
        $address->setStreet('Seaside Fwy');
        $address->setCity('Rosario');
        $address->setCountryCode('AR');
        $address->setPostcode('2000');
        $order->setBillingAddress($address);

        $method = new PaymentMethod();
        $method->setCurrentLocale('en_US');
        $method->setFallbackLocale('en_US');
        $method->setName('Cash on delivery');
        $payment = new Payment();
        $payment->setMethod($method);
        $order->addPayment($payment);

        $coupon = new PromotionCoupon();
        $coupon->setCode('WELCOME');
        $order->setPromotionCoupon($coupon);

        return $order;
    }
}
