<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order;

use Odiseo\SyliusBrevoPlugin\Client\Model\OrderData;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Odiseo\SyliusBrevoPlugin\Ecommerce\AccountMoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Order\Payload\OrderPayloadProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderPayloadBuilder implements OrderPayloadBuilderInterface
{
    /** Fields merged by key across providers. */
    private const MERGED = ['metaInfo', 'billing'];

    /** @param iterable<OrderPayloadProviderInterface> $providers later ones win on the same field */
    public function __construct(
        private readonly iterable $providers,
        private readonly OrderStatusMapperInterface $statusMapper,
        private readonly AccountMoneyFormatterInterface $moneyFormatter,
    ) {
    }

    public function build(OrderInterface $order): ?OrderData
    {
        $completedAt = $order->getCheckoutCompletedAt();
        $channel = $order->getChannel();
        $customer = $order->getCustomer();
        $number = $order->getNumber();
        if (null === $completedAt || !$channel instanceof ChannelInterface || !$customer instanceof CustomerInterface || null === $number) {
            return null;
        }

        $currency = (string) $order->getCurrencyCode();
        $products = [];
        foreach ($order->getItems() as $item) {
            $code = $item->getVariant()?->getCode();
            if (null === $code || 0 === $item->getQuantity()) {
                continue;
            }

            // Unit price after discounts.
            $products[] = [
                'productId' => $code,
                'quantity' => $item->getQuantity(),
                'price' => round($this->moneyFormatter->format($item->getTotal(), $currency, $channel) / $item->getQuantity(), 2),
            ];
        }

        $fields = ['identifiers' => array_filter(['email_id' => $customer->getEmail(), 'ext_id' => ContactExtId::of($customer)], self::isKnown(...))];
        foreach ($this->providers as $provider) {
            foreach ($provider->provide($order, $channel) as $field => $value) {
                $fields[$field] = in_array($field, self::MERGED, true) && is_array($value) ? [...(array) ($fields[$field] ?? []), ...$value] : $value;
            }
        }
        foreach (self::MERGED as $field) {
            // Left out when empty: it would be encoded as a JSON list, not an object.
            $fields[$field] = array_filter((array) ($fields[$field] ?? []), self::isKnown(...)) ?: null;
        }

        return new OrderData(
            $number,
            $this->statusMapper->map($order),
            $this->moneyFormatter->format($order->getTotal(), $currency, $channel),
            $completedAt,
            $order->getUpdatedAt() ?? $completedAt,
            $products,
            array_filter($fields, self::isKnown(...)),
        );
    }

    private static function isKnown(mixed $value): bool
    {
        return null !== $value && '' !== $value;
    }
}
