<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Controller\Webhook\Index;
use Swarming\SubscribePro\Model\Config\Advanced;
use Swarming\SubscribePro\Platform\Service\Webhook;
use Swarming\SubscribePro\Platform\Webhook\Processor;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    protected $indexController;
    protected $webhookProcessorMock;
    protected $platformWebhookServiceMock;
    protected $advancedConfigMock;
    protected $requestMock;
    protected $resultMock;
    protected $loggerMock;

    protected function setUp(): void
    {
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->webhookProcessorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformWebhookServiceMock = $this->getMockBuilder(Webhook::class)
            ->disableOriginalConstructor()->getMock();
        $this->advancedConfigMock = $this->getMockBuilder(Advanced::class)
            ->disableOriginalConstructor()->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->getMock();
        $this->resultMock = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();
        $factory = $this->getMockBuilder(JsonFactory::class)->disableOriginalConstructor()->getMock();
        $factory->expects($this->once())->method('create')->willReturn($this->resultMock);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->indexController = new Index(
            $context,
            $this->webhookProcessorMock,
            $this->platformWebhookServiceMock,
            $this->advancedConfigMock,
            $factory,
            $this->requestMock,
            $this->loggerMock
        );
    }

    /**
     * @dataProvider invalidHashDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidHashDataProvider')]
    public function testExecuteRejectsInvalidHash($hash)
    {
        $this->configureHash($hash);
        $this->platformWebhookServiceMock->expects($this->never())->method('readEvent');
        $this->webhookProcessorMock->expects($this->never())->method('processEvent');
        $this->expectResponse(403, [
            'error_message' => __('Could not validate webhook source using hash. Check secret key.')
        ]);
        $this->assertSame($this->resultMock, $this->indexController->execute());
    }

    public static function invalidHashDataProvider()
    {
        return [
            'missing' => [false],
            'incorrect' => ['invalid'],
            'different body' => [hash_hmac('sha256', 'other body', 'secret')],
            'different secret' => [hash_hmac('sha256', 'body', 'other secret')]
        ];
    }

    public function testExecuteIfNoEvent()
    {
        $this->configureHash(hash_hmac('sha256', 'body', 'secret'));
        $this->platformWebhookServiceMock->expects($this->once())->method('readEvent')->willReturn(false);
        $this->webhookProcessorMock->expects($this->never())->method('processEvent');
        $this->expectResponse(400, [
            'error_message' => __('Webhook data is malformed or did not contain a valid event')
        ]);
        $this->assertSame($this->resultMock, $this->indexController->execute());
    }

    public function testExecuteIfFailToProcessEvent()
    {
        $exception = new \InvalidArgumentException('error');
        $this->configureEvent()->willThrowException($exception);
        $this->loggerMock->expects($this->once())->method('critical')->with($exception);
        $this->expectResponse(500, ['error_message' => 'error']);
        $this->assertSame($this->resultMock, $this->indexController->execute());
    }

    public function testExecuteIfEventIsNotFound()
    {
        $exception = new NoSuchEntityException(__('Event not found.'));
        $this->configureEvent()->willThrowException($exception);
        $this->loggerMock->expects($this->never())->method('critical');
        $this->expectResponse(404, ['error_message' => 'Event not found.']);
        $this->assertSame($this->resultMock, $this->indexController->execute());
    }

    public function testExecute()
    {
        $this->configureEvent();
        $this->loggerMock->expects($this->never())->method('critical');
        $this->expectResponse(200, null);
        $this->assertSame($this->resultMock, $this->indexController->execute());
    }

    private function configureHash($hash)
    {
        $this->advancedConfigMock->expects($this->once())->method('getWebhookSecretKey')->willReturn('secret');
        $this->requestMock->expects($this->once())->method('getHeader')
            ->with(Index::WEBHOOK_HASH_HEADER_KEY)->willReturn($hash);
        $this->requestMock->expects($this->once())->method('getContent')->willReturn('body');
    }

    private function configureEvent()
    {
        $this->configureHash(hash_hmac('sha256', 'body', 'secret'));
        $event = $this->createMock(EventInterface::class);
        $this->platformWebhookServiceMock->expects($this->once())->method('readEvent')->willReturn($event);
        return $this->webhookProcessorMock->expects($this->once())->method('processEvent')->with($event);
    }

    private function expectResponse($status, $data)
    {
        $this->resultMock->expects($this->once())->method('setHttpResponseCode')->with($status)->willReturnSelf();
        $this->resultMock->expects($this->once())->method('setData')->with($data)->willReturnSelf();
    }
}
