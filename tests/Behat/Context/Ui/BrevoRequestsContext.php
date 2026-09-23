<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;
use Webmozart\Assert\Assert;

final class BrevoRequestsContext implements Context
{
    public function __construct(
        private readonly FakeBrevoHttpClient $fakeBrevoHttpClient,
    ) {
    }

    /**
     * @Then Brevo should have received the order
     */
    public function brevoShouldHaveReceivedTheOrder(): void
    {
        Assert::count($this->fakeBrevoHttpClient->requests('POST', '/dummy/orders'), 1);
    }
}
