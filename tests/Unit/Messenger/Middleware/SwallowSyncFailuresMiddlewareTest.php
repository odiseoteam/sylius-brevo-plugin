<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Messenger\Middleware;

use Odiseo\SyliusBrevoPlugin\Client\Exception\ServerException;
use Odiseo\SyliusBrevoPlugin\Logging\SensitiveDataMasker;
use Odiseo\SyliusBrevoPlugin\Messenger\Middleware\SwallowSyncFailuresMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyOrderPlaced;
use Tests\Odiseo\SyliusBrevoPlugin\Double\InMemoryLogger;

final class SwallowSyncFailuresMiddlewareTest extends TestCase
{
    private InMemoryLogger $logger;

    private SwallowSyncFailuresMiddleware $middleware;

    protected function setUp(): void
    {
        $this->logger = new InMemoryLogger();
        $this->middleware = new SwallowSyncFailuresMiddleware($this->logger, new SensitiveDataMasker());
    }

    public function testItLogsAndSwallowsFailuresOfDispatchedMessages(): void
    {
        $envelope = new Envelope(new DummyOrderPlaced('WEB', '1'));

        $result = $this->middleware->handle($envelope, $this->failingStack($envelope, new ServerException('Brevo is down for john@example.com', 503)));

        self::assertSame($envelope, $result);
        self::assertCount(1, $this->logger->records);
        self::assertSame('error', $this->logger->records[0]['level']);
        self::assertSame('WEB', $this->logger->records[0]['context']['channel']);
        self::assertSame('Brevo is down for j***@example.com', $this->logger->records[0]['context']['error']);
    }

    public function testItRethrowsForReceivedMessages(): void
    {
        $envelope = (new Envelope(new DummyOrderPlaced('WEB', '1')))->with(new ReceivedStamp('odiseo_brevo'));

        $this->expectException(HandlerFailedException::class);

        $this->middleware->handle($envelope, $this->failingStack($envelope, new ServerException('down', 503)));
    }

    public function testItIgnoresOtherMessages(): void
    {
        $envelope = new Envelope(new \stdClass());

        $this->expectException(HandlerFailedException::class);

        $this->middleware->handle($envelope, $this->failingStack($envelope, new \RuntimeException('not ours')));
    }

    private function failingStack(Envelope $envelope, \Throwable $exception): StackInterface
    {
        $failing = new class(new HandlerFailedException($envelope, [$exception])) implements MiddlewareInterface {
            public function __construct(private readonly \Throwable $exception)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                throw $this->exception;
            }
        };

        return new StackMiddleware($failing);
    }
}
