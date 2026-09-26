<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Hook;

use Behat\Behat\Context\Context;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;

final class BrevoFakeClientContext implements Context
{
    public function __construct(
        private readonly FakeBrevoHttpClient $fakeBrevoHttpClient,
    ) {
    }

    /** @BeforeScenario */
    public function resetFakeClient(): void
    {
        $this->fakeBrevoHttpClient->reset();
    }
}
