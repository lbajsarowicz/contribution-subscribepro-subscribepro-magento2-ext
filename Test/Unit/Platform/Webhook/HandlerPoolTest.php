<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook;

use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerPool;

class HandlerPoolTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $handlers
     * @param string $eventType
     * @dataProvider getHandlerIfHandlerNotFoundDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getHandlerIfHandlerNotFoundDataProvider')]
    public function testGetHandlerIfHandlerNotFound($handlers, $eventType)
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Handler for '{$eventType}' event is not found.");
        $handlerPool = new HandlerPool($handlers);
        $handlerPool->getHandler($eventType);
    }

    /**
     * @return array
     */
    public static function getHandlerIfHandlerNotFoundDataProvider()
    {
        return [
            'Event type not set' => [
                'handlers' => ['some_type' => 'unused'],
                'eventType' => 'another_type'
            ],
            'Event not implement HandlerInterface' => [
                'handlers' => ['my_type' => 'string'],
                'eventType' => 'my_type'
            ]
        ];
    }

    public function testGetHandler()
    {
        $eventType = 'my_type';
        $handler = $this->createWebhookHandlerMock();

        $handlers = [
            'type' => $this->createWebhookHandlerMock(),
            $eventType => $handler
        ];

        $handlerPool = new HandlerPool($handlers);
        $this->assertSame($handler, $handlerPool->getHandler($eventType));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\HandlerInterface
     */
    private function createWebhookHandlerMock()
    {
        return $this->createStub(HandlerInterface::class);
    }
}
