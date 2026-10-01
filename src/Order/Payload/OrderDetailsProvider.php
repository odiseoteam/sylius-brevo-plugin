<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order\Payload;

use Odiseo\SyliusBrevoPlugin\Ecommerce\AccountMoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Phone\PhoneNumberNormalizerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;

/** Billing address and payment method, coupon, and the totals breakdown as metaInfo. */
final class OrderDetailsProvider implements OrderPayloadProviderInterface
{
    public function __construct(
        private readonly AccountMoneyFormatterInterface $accountMoneyFormatter,
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly PhoneNumberNormalizerInterface $phoneNumberNormalizer,
    ) {
    }

    public function provide(OrderInterface $order, ChannelInterface $channel): array
    {
        $currency = (string) $order->getCurrencyCode();
        $address = $order->getBillingAddress();
        $shipment = $order->getShipments()->first();
        $coupon = $order->getPromotionCoupon()?->getCode();
        $money = fn (int $amount): float => $this->accountMoneyFormatter->format($amount, $currency, $channel);

        return [
            'billing' => [
                'address' => $address?->getStreet(),
                'city' => $address?->getCity(),
                'countryCode' => $address?->getCountryCode(),
                'postCode' => $address?->getPostcode(),
                'region' => $address?->getProvinceName() ?? $address?->getProvinceCode(),
                'phone' => $this->phoneNumberNormalizer->normalize($address?->getPhoneNumber(), $address?->getCountryCode(), $channel),
                'paymentMethod' => $order->getLastPayment()?->getMethod()?->getName(),
            ],
            'coupons' => null === $coupon ? null : [$coupon],
            'metaInfo' => [
                'currency' => $currency,
                'original_amount' => $this->accountMoneyFormatter->converts($currency, $channel) ? $this->moneyFormatter->format($order->getTotal(), $currency) : null,
                'items_total' => $money($order->getItemsTotal()),
                'shipping_total' => $money($order->getShippingTotal()),
                'tax_total' => $money($order->getTaxTotal()),
                'discount_total' => $money(-$order->getOrderPromotionTotal()),
                'shipping_method' => $shipment instanceof ShipmentInterface ? $shipment->getMethod()?->getName() : null,
            ],
        ];
    }
}
