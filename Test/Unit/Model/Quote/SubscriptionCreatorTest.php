<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use SubscribePro\Service\Customer\CustomerInterface;
use Swarming\SubscribePro\Helper\OrderItem;
use Swarming\SubscribePro\Helper\QuoteItem;
use Swarming\SubscribePro\Model\Quote\Payment\GetPaymentProfileId;
use Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator as ItemSubscriptionCreator;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Platform\Manager\Customer;
use Swarming\SubscribePro\Platform\Service\Subscription;

class SubscriptionCreatorTest extends \PHPUnit\Framework\TestCase
{
    private $creator;
    private $customerManager;
    private $paymentProfileId;
    private $itemHelper;
    private $orderItemHelper;
    private $itemCreator;

    protected function setUp(): void
    {
        $this->customerManager = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
        $this->paymentProfileId = $this->getMockBuilder(GetPaymentProfileId::class)
            ->disableOriginalConstructor()->getMock();
        $this->itemHelper = $this->createStub(QuoteItem::class);
        $this->orderItemHelper = $this->getMockBuilder(OrderItem::class)->disableOriginalConstructor()->getMock();
        $this->itemCreator = $this->getMockBuilder(ItemSubscriptionCreator::class)
            ->disableOriginalConstructor()->getMock();
        $this->creator = new SubscriptionCreator(
            $this->createStub(Subscription::class),
            $this->customerManager,
            $this->createStub(PaymentTokenManagementInterface::class),
            $this->itemHelper,
            $this->orderItemHelper,
            $this->itemCreator,
            $this->paymentProfileId
        );
    }

    public function testCreateSubscriptionsIfPaymentProfileUnavailable()
    {
        $quote = $this->createMock(SubscriptionQuoteInterface::class);
        $quote->method('getCustomerId')->willReturn(23);
        $quote->expects($this->never())->method('getAllShippingAddresses');
        $quote->expects($this->never())->method('getAllVisibleItems');
        $payment = $this->createStub(OrderPaymentInterface::class);
        $order = $this->createStub(OrderInterface::class);
        $order->method('getPayment')->willReturn($payment);
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getId')->willReturn(42);
        $this->customerManager->expects($this->once())->method('getCustomerById')->with(23, true)->willReturn($customer);
        $this->paymentProfileId->expects($this->once())->method('execute')->with($payment, 42)
            ->willThrowException(new \UnexpectedValueException('The vault is not found.'));
        $this->itemCreator->expects($this->never())->method('create');
        $this->orderItemHelper->expects($this->never())->method('updateOrderItem');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The vault is not found.');
        $this->creator->createSubscriptions($quote, $order);
    }

    /**
     * @dataProvider subscriptionsDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('subscriptionsDataProvider')]
    public function testCreateSubscriptions($shippingResults, $virtualResult, $enabled, $fulfilling)
    {
        $quote = $this->createMock(SubscriptionQuoteInterface::class);
        $quote->method('getCustomerId')->willReturn(23);
        $payment = $this->createStub(OrderPaymentInterface::class);
        $order = $this->createStub(OrderInterface::class);
        $order->method('getPayment')->willReturn($payment);
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getId')->willReturn(42);
        $this->customerManager->expects($this->once())->method('getCustomerById')->with(23, true)->willReturn($customer);
        $this->paymentProfileId->expects($this->once())->method('execute')->with($payment, 42)->willReturn('123');
        $this->itemHelper->method('getCreateNewSubscriptionAtCheckout')->willReturn($enabled);
        $this->itemHelper->method('isItemFulfilsSubscription')->willReturn($fulfilling);

        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $billing = $this->createStub(Address::class);
        $shippingItems = [];
        $virtualItems = [];
        $expectedCalls = [];
        foreach ($shippingResults as $index => $result) {
            $item = $this->createItem($index + 1, false);
            $shippingItems[] = $item;
            if ($enabled && !$fulfilling) {
                $expectedCalls[] = [$item, 42, '123', $address, $billing, $result];
            }
        }
        if ($virtualResult !== false) {
            $item = $this->createItem(99, true);
            $virtualItems[] = $item;
            if ($enabled && !$fulfilling) {
                $expectedCalls[] = [$item, 42, '123', null, null, $virtualResult];
            }
        }
        $quote->expects($this->once())->method('getAllShippingAddresses')->willReturn([$address]);
        $quote->method('getBillingAddress')->willReturn($billing);
        $quote->expects($this->once())->method('getAllVisibleItems')->willReturn(array_merge($shippingItems, $virtualItems));
        $address->expects($this->once())->method('getAllVisibleItems')->willReturn($shippingItems);
        $calls = [];
        $this->itemCreator->expects($this->exactly(count($expectedCalls)))->method('create')
            ->willReturnCallback(function ($item, $customerId, $profileId, $shipping = null, $billing = null) use (
                &$calls,
                $expectedCalls
            ) {
                $expected = $expectedCalls[count($calls)];
                $calls[] = [$item, $customerId, $profileId, $shipping, $billing];
                $this->assertSame(array_slice($expected, 0, 5), end($calls));
                return $expected[5];
            });
        $created = [];
        $updatedItems = [];
        $expectedUpdatedItems = [];
        $failed = 0;
        foreach ($expectedCalls as $expected) {
            if ($expected[5]) {
                $created[] = $expected[5];
                $expectedUpdatedItems[] = [$order, $expected[0]->getItemId(), $expected[5]];
            } else {
                $failed++;
            }
        }
        $this->orderItemHelper->expects($this->exactly(count($created)))->method('updateOrderItem')
            ->willReturnCallback(function ($order, $itemId, $subscriptionId) use (&$updatedItems) {
                $updatedItems[] = [$order, $itemId, $subscriptionId];
            });

        $this->assertSame([
            SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => $created,
            SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => $failed,
        ], $this->creator->createSubscriptions($quote, $order));
        $this->assertSame($expectedUpdatedItems, $updatedItems);
    }

    public static function subscriptionsDataProvider()
    {
        return [
            'Empty quote' => [[], false, true, false],
            'Subscription not enabled' => [[12], false, false, false],
            'Quote item fulfils subscription' => [[12], false, true, true],
            'Virtual product not enabled' => [[], 12, false, false],
            'All quote items valid' => [[12, 34], false, true, false],
            'With not created subscriptions' => [[null, 34, 56], false, true, false],
            'Fail to create subscription from virtual product' => [[123, null, 78], null, true, false],
            'Subscription created for virtual product' => [[123, null, 78], 431, true, false],
        ];
    }

    private function createItem($id, $virtual)
    {
        $item = $this->createStub(SubscriptionQuoteItem::class);
        $item->method('getItemId')->willReturn($id);
        $item->method('getIsVirtual')->willReturn($virtual);
        return $item;
    }
}
