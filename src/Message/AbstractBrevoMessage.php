<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Message;

abstract class AbstractBrevoMessage implements BrevoMessageInterface
{
    public function __construct(
        private readonly string $channelCode,
        private readonly string $idempotencyKey,
    ) {
    }

    public function getChannelCode(): string
    {
        return $this->channelCode;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }
}
