<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook;

use SubscribePro\Service\Webhook\EventInterface as WebhookEventInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerPool;
use Swarming\SubscribePro\Platform\Webhook\Processor;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\Processor
     */
    protected $webhookProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\HandlerPool
     */
    protected $webhookHandlerPoolMock;

    private $loggerMock;

    protected function setUp(): void
    {
        $this->webhookHandlerPoolMock = $this->getMockBuilder(HandlerPool::class)
            ->disableOriginalConstructor()->getMock();

        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $this->webhookProcessor = new Processor($this->webhookHandlerPoolMock, $this->loggerMock);
    }

    public function testProcessEventIfFailToGetHandler()
    {
        $exception = new \DomainException('error');
        $this->loggerMock->expects($this->once())->method('debug')->with('error');
        $this->loggerMock->expects($this->once())->method('warning')->with('Error while process webhook!');
        $eventType = 'some_type';

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->once())->method('getType')->willReturn($eventType);

        $this->webhookHandlerPoolMock->expects($this->once())
            ->method('getHandler')
            ->with($eventType)
            ->willThrowException($exception);

        $this->webhookProcessor->processEvent($webhookEventMock);
    }

    public function testProcessEvent()
    {
        $this->loggerMock->expects($this->never())->method('debug');
        $this->loggerMock->expects($this->never())->method('warning');
        $eventType = 'some_type';

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->once())->method('getType')->willReturn($eventType);

        $eventHandler = $this->createWebhookHandlerMock();
        $eventHandler->expects($this->once())->method('execute')->with($webhookEventMock);

        $this->webhookHandlerPoolMock->expects($this->once())
            ->method('getHandler')
            ->with($eventType)
            ->willReturn($eventHandler);

        $this->webhookProcessor->processEvent($webhookEventMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\HandlerInterface
     */
    private function createWebhookHandlerMock()
    {
        return $this->getMockBuilder(HandlerInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\EventInterface
     */
    private function createWebhookEventMock()
    {
        return $this->getMockBuilder(WebhookEventInterface::class)->getMock();
    }
}
