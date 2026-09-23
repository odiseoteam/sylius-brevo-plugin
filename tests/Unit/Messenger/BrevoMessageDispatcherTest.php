<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Messenger;

use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyOrderPlaced;
use Tests\Odiseo\SyliusBrevoPlugin\Double\InMemoryLogger;

final class BrevoMessageDispatcherTest extends TestCase
{
    /** @var list<object> */
    private array $dispatched = [];

    private RequestStack $requestStack;

    private InMemoryLogger $logger;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->logger = new InMemoryLogger();
    }

    public function testItDispatchesRightAwayOutsideARequest(): void
    {
        $this->dispatcher()->dispatch(new DummyOrderPlaced('WEB', '1'));

        self::assertCount(1, $this->dispatched);
    }

    public function testItWaitsForTheResponseWithinARequest(): void
    {
        $this->requestStack->push(new Request());
        $dispatcher = $this->dispatcher();

        $dispatcher->dispatch(new DummyOrderPlaced('WEB', '1'));
        $dispatcher->dispatch(new DummyOrderPlaced('WEB', '2'));
        self::assertCount(0, $this->dispatched);

        $dispatcher->flush();
        self::assertCount(2, $this->dispatched);

        $dispatcher->flush();
        self::assertCount(2, $this->dispatched);
    }

    public function testAFailedRequestDropsItsMessages(): void
    {
        $this->requestStack->push(new Request());
        $dispatcher = $this->dispatcher();

        $dispatcher->dispatch(new DummyOrderPlaced('WEB', '1'));
        $dispatcher->reset();
        $dispatcher->flush();

        self::assertCount(0, $this->dispatched);
    }

    public function testItNeverThrows(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new \RuntimeException('transport down'));

        (new BrevoMessageDispatcher($bus, $this->requestStack, $this->logger))->dispatch(new DummyOrderPlaced('WEB', '1'));

        self::assertSame('transport down', $this->logger->records[0]['context']['error']);
    }

    private function dispatcher(): BrevoMessageDispatcher
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message): Envelope {
            $this->dispatched[] = $message;

            return new Envelope($message);
        });

        return new BrevoMessageDispatcher($bus, $this->requestStack, $this->logger);
    }
}
