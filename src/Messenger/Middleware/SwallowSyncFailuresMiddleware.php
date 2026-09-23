<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Messenger\Middleware;

use Odiseo\SyliusBrevoPlugin\Logging\SensitiveDataMasker;
use Odiseo\SyliusBrevoPlugin\Message\BrevoMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Logs and swallows failures of messages dispatched from app code (sync transport or sending).
 * Received messages rethrow, so workers can retry them.
 */
final class SwallowSyncFailuresMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SensitiveDataMasker $masker,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if (!$message instanceof BrevoMessageInterface || null !== $envelope->last(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $cause = $exception instanceof HandlerFailedException ? ($exception->getPrevious() ?? $exception) : $exception;

            $this->logger->error('Brevo message failed', [
                'message' => $message::class,
                'channel' => $message->getChannelCode(),
                'key' => $this->masker->mask($message->getIdempotencyKey()),
                'error' => $this->masker->mask($cause->getMessage()),
            ]);

            return $envelope;
        }
    }
}
