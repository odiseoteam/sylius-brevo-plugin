<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Ecommerce;

use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatterInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\ExchangeRateInterface;
use Sylius\Component\Currency\Repository\ExchangeRateRepositoryInterface;

final class AccountMoneyFormatter implements AccountMoneyFormatterInterface
{
    /** @var array<string, true> pairs already warned about */
    private array $warned = [];

    /** @param ExchangeRateRepositoryInterface<ExchangeRateInterface> $exchangeRateRepository */
    public function __construct(
        private readonly EcommerceAccountsInterface $accounts,
        private readonly ExchangeRateRepositoryInterface $exchangeRateRepository,
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function format(int $amount, string $currencyCode, ChannelInterface $channel): float
    {
        $target = $this->accounts->currencyOf($channel);
        if (null === $target || $target === $currencyCode) {
            return $this->moneyFormatter->format($amount, $currencyCode);
        }

        $rate = $this->exchangeRateRepository->findOneWithCurrencyPair($currencyCode, $target);
        if (null === $rate || null === $rate->getRatio()) {
            $this->warn($currencyCode, $target);

            return $this->moneyFormatter->format($amount, $currencyCode);
        }

        $converted = $rate->getSourceCurrency()?->getCode() === $currencyCode ? $amount * $rate->getRatio() : $amount / $rate->getRatio();

        return $this->moneyFormatter->format((int) round($converted), $target);
    }

    public function converts(string $currencyCode, ChannelInterface $channel): bool
    {
        $target = $this->accounts->currencyOf($channel);

        return null !== $target && $target !== $currencyCode && null !== $this->exchangeRateRepository->findOneWithCurrencyPair($currencyCode, $target)?->getRatio();
    }

    private function warn(string $currencyCode, string $target): void
    {
        if (isset($this->warned[$currencyCode . $target])) {
            return;
        }

        $this->warned[$currencyCode . $target] = true;
        $this->logger->warning('No exchange rate from {from} to {to}: amounts are sent to Brevo unconverted.', ['from' => $currencyCode, 'to' => $target]);
    }
}
