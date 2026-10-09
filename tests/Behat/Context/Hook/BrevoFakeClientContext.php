<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Hook;

use Behat\Behat\Context\Context;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcher;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;

final class BrevoFakeClientContext implements Context
{
    public function __construct(
        private readonly FakeBrevoHttpClient $fakeBrevoHttpClient,
        private readonly BrevoMessageDispatcher $dispatcher,
    ) {
    }

    /** @BeforeScenario */
    public function resetFakeClient(): void
    {
        $this->fakeBrevoHttpClient->reset();
        $this->dispatcher->reset();
    }

    /**
     * Setup steps run with a request on the stack but never terminate it: send what they queued.
     *
     * @AfterStep
     */
    public function sendQueuedMessages(): void
    {
        $this->dispatcher->flush();
    }
}
