<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\ThirdPartyPayment;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Observer\Checkout\SubmitAllAfter;

class SubmitAllAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Checkout\SubmitAllAfter
     */
    protected $checkoutSubmitAllAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Checkout\Model\Session
     */
    protected $checkoutSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    protected $subscriptionCreatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Swarming\SubscribePro\Helper\ThirdPartyPayment
     */
    protected $thirdPartyPaymentMock;

    protected function setUp(): void
    {
        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMock();

        $this->subscriptionCreatorMock = $this->getMockBuilder(SubscriptionCreator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->thirdPartyPaymentMock = $this->getMockBuilder(ThirdPartyPayment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSubmitAllAfter = new SubmitAllAfter(
            $this->generalConfigMock,
            $this->checkoutSessionMock,
            $this->subscriptionCreatorMock,
            $this->getMockBuilder(CartItemOptionsProcessor::class)->disableOriginalConstructor()->getMock(),
            $this->loggerMock,
            $this->thirdPartyPaymentMock
        );
    }

    public function testExecuteIfModuleNotEnabled()
    {
        $websiteCode = 'code';

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->never())
            ->method('getPayment');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->never())
            ->method('getCustomerId');

        $observerMock = $this->createObserverMock();
        $getDataCalls = [
            ['quote', $quoteMock],
            ['order', $orderMock],
        ];
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($arg) use (&$getDataCalls) {
                [$expectedArg, $result] = array_shift($getDataCalls);
                $this->assertEquals($expectedArg, $arg);
                return $result;
            });

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(false);

        $this->subscriptionCreatorMock->expects($this->never())
            ->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())
            ->method('setData');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecuteIfPaymentNotSubscribePro()
    {
        $websiteCode = 'some_website_code';

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn('any_payment_except_subscribe_pro');

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(123);

        $this->thirdPartyPaymentMock->expects($this->once())
            ->method('isThirdPartyPaymentMethodAllowed')
            ->with('any_payment_except_subscribe_pro', 0)
            ->willReturn(false);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->never())
            ->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())
            ->method('setData');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecuteIfCustomerIdIsEmpty()
    {
        $websiteCode = 'some_website_code';

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->never())
            ->method('getPayment');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->never())
            ->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())
            ->method('setData');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecuteIfFailToCreateSubscriptions()
    {
        $exception = new \Exception('error');

        $websiteCode = 'website_code';
        $customerId = 123;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->once())
            ->method('createSubscriptions')
            ->with($quoteMock, $orderMock)
            ->willThrowException($exception);

        $setDataCalls = [
            [SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, []],
            [SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, 0],
        ];
        $this->checkoutSessionMock->expects($this->exactly(2))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$setDataCalls) {
                $this->assertEquals(array_shift($setDataCalls), [$key, $value]);
                return $this->checkoutSessionMock;
            });

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecute()
    {
        $websiteCode = 'website_code';
        $customerId = 321;
        $createdSubscriptionIds = [11, 12, 13];
        $failedSubscriptionsCount = 2;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->once())
            ->method('createSubscriptions')
            ->with($quoteMock, $orderMock)
            ->willReturn([
                SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => $createdSubscriptionIds,
                SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => $failedSubscriptionsCount
            ]);

        $setDataCalls = [
            [SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, $createdSubscriptionIds],
            [SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, $failedSubscriptionsCount],
        ];
        $this->checkoutSessionMock->expects($this->exactly(2))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$setDataCalls) {
                $this->assertEquals(array_shift($setDataCalls), [$key, $value]);
                return $this->checkoutSessionMock;
            });

        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderInterface
     */
    private function createOrderMock()
    {
        return $this->getMockBuilder(OrderInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderPaymentInterface
     */
    private function createPaymentMock()
    {
        return $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\Store
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\WebsiteInterface
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote
     */
    private function createQuoteMock()
    {
        $quoteMock = $this->getMockBuilder(CustomerQuote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore', 'getCustomerId', 'getAllItems', '__wakeup'])
            ->getMock();
        $quoteMock->method('getAllItems')->willReturn([]);

        return $quoteMock;
    }
}
