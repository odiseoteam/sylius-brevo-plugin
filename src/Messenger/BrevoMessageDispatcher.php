<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Messenger;

use Odiseo\SyliusBrevoPlugin\Message\BrevoMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Within a request, messages wait until the response is sent (kernel.terminate): nothing leaves
 * before the flush and a sync transport never slows the shop down. A failed request drops them.
 * Elsewhere (CLI, workers) they go right away. The same change queued twice is sent once.
 */
final class BrevoMessageDispatcher implements BrevoMessageDispatcherInterface, EventSubscriberInterface, ResetInterface
{
    /** @var array<string, BrevoMessageInterface> */
    private array $pending = [];

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'flush',
            KernelEvents::EXCEPTION => 'reset',
        ];
    }

    public function dispatch(BrevoMessageInterface $message): void
    {
        if (null !== $this->requestStack->getMainRequest()) {
            $this->pending[$message::class . '|' . $message->getIdempotencyKey()] = $message;

            return;
        }

        $this->send($message);
    }

    public function flush(): void
    {
        $pending = $this->pending;
        $this->pending = [];

        foreach ($pending as $message) {
            $this->send($message);
        }
    }

    public function reset(): void
    {
        $this->pending = [];
    }

    private function send(BrevoMessageInterface $message): void
    {
        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $exception) {
            // E.g. the transport is down; the bus middleware already covers handler failures.
            $this->logger->error('Brevo message could not be dispatched', [
                'message' => $message::class,
                'channel' => $message->getChannelCode(),
                'key' => $message->getIdempotencyKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
