<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Ecommerce;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\ExchangeRateInterface;
use Sylius\Component\Currency\Repository\ExchangeRateRepositoryInterface;

final class MissingExchangeRates implements MissingExchangeRatesInterface
{
    /** @param ExchangeRateRepositoryInterface<ExchangeRateInterface> $exchangeRateRepository */
    public function __construct(
        private readonly EcommerceAccountsInterface $accounts,
        private readonly ExchangeRateRepositoryInterface $exchangeRateRepository,
    ) {
    }

    public function of(ChannelInterface $channel): array
    {
        foreach ($this->accounts->channelsByAccount() as $channels) {
            $codes = array_map(static fn (ChannelInterface $accountChannel): ?string => $accountChannel->getCode(), $channels);
            if (!in_array($channel->getCode(), $codes, true)) {
                continue;
            }

            $target = $channels[0]->getBaseCurrency()?->getCode();
            $missing = [];
            foreach ($channels as $accountChannel) {
                $currency = $accountChannel->getBaseCurrency()?->getCode();
                if (null !== $target && null !== $currency && $currency !== $target && null === $this->exchangeRateRepository->findOneWithCurrencyPair($currency, $target)) {
                    $missing[] = ['channel' => (string) $accountChannel->getName(), 'from' => $currency, 'to' => $target];
                }
            }

            return $missing;
        }

        return [];
    }
}
