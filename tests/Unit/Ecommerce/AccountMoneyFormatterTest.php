<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Ecommerce;

use Odiseo\SyliusBrevoPlugin\Ecommerce\AccountMoneyFormatter;
use Odiseo\SyliusBrevoPlugin\Ecommerce\EcommerceAccountsInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Currency\Model\ExchangeRate;
use Sylius\Component\Currency\Repository\ExchangeRateRepositoryInterface;

final class AccountMoneyFormatterTest extends TestCase
{
    private Channel $channel;

    protected function setUp(): void
    {
        $this->channel = new Channel();
        $this->channel->setCode('UK');
    }

    public function testTheAccountCurrencyIsKept(): void
    {
        self::assertSame(12.5, $this->formatter(null)->format(1250, 'USD', $this->channel));
        self::assertFalse($this->formatter(null)->converts('USD', $this->channel));
    }

    public function testOtherCurrenciesAreConvertedEitherWay(): void
    {
        $rate = $this->rate('GBP', 'USD', 1.25);

        self::assertSame(12.5, $this->formatter($rate)->format(1000, 'GBP', $this->channel));
        self::assertTrue($this->formatter($rate)->converts('GBP', $this->channel));

        $inverse = $this->rate('USD', 'GBP', 0.8);
        self::assertSame(12.5, $this->formatter($inverse)->format(1000, 'GBP', $this->channel));
    }

    public function testWithoutARateTheAmountIsSentUnconvertedAndWarnedOnce(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $formatter = $this->formatter(null, $logger);

        self::assertSame(10.0, $formatter->format(1000, 'GBP', $this->channel));
        self::assertSame(20.0, $formatter->format(2000, 'GBP', $this->channel));
        self::assertFalse($formatter->converts('GBP', $this->channel));
    }

    private function formatter(?ExchangeRate $rate, ?LoggerInterface $logger = null): AccountMoneyFormatter
    {
        $accounts = $this->createStub(EcommerceAccountsInterface::class);
        $accounts->method('currencyOf')->willReturn('USD');
        $repository = $this->createStub(ExchangeRateRepositoryInterface::class);
        $repository->method('findOneWithCurrencyPair')->willReturn($rate);

        return new AccountMoneyFormatter($accounts, $repository, new MoneyFormatter(), $logger ?? $this->createStub(LoggerInterface::class));
    }

    private function rate(string $source, string $target, float $ratio): ExchangeRate
    {
        $sourceCurrency = new Currency();
        $sourceCurrency->setCode($source);
        $targetCurrency = new Currency();
        $targetCurrency->setCode($target);

        $rate = new ExchangeRate();
        $rate->setSourceCurrency($sourceCurrency);
        $rate->setTargetCurrency($targetCurrency);
        $rate->setRatio($ratio);

        return $rate;
    }
}
