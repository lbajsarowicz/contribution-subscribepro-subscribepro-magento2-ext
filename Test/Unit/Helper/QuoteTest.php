<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Quote\Model\Quote as QuoteModel;
use Swarming\SubscribePro\Helper\Quote;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;

class QuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp(): void
    {
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->quoteHelper = new Quote(
            $this->quoteItemHelperMock
        );
    }

    /**
     * @param iterable $items
     * @dataProvider hasSubscriptionIfNoItemsDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('hasSubscriptionIfNoItemsDataProvider')]
    public function testHasSubscriptionIfNoItems($items)
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getItemsCollection')->willReturn($items);

        $this->assertFalse($this->quoteHelper->hasSubscription($quoteMock));
    }

    /**
     * @return array
     */
    public static function hasSubscriptionIfNoItemsDataProvider()
    {
        return [
            'Empty iterator' => ['items' => new \ArrayIterator([])],
            'Empty items' => ['items' => []],
        ];
    }

    /**
     * @param int $itemCount
     * @param bool[] $hasSubscriptionItemResults
     * @param bool $hasSubscription
     * @dataProvider hasSubscriptionDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('hasSubscriptionDataProvider')]
    public function testHasSubscription(
        $itemCount,
        $hasSubscriptionItemResults,
        $hasSubscription
    ) {
        $quoteItems = [];
        for ($index = 0; $index < $itemCount; $index++) {
            $quoteItems[] = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        }
        $quoteItemHasSubscriptionMap = [];
        foreach ($quoteItems as $key => $quoteItem) {
            $quoteItemHasSubscriptionMap[] = [$quoteItems[$key], $hasSubscriptionItemResults[$key]];
        }

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getItemsCollection')->willReturn($quoteItems);

        $this->quoteItemHelperMock->expects($this->exactly(count($quoteItems)))
            ->method('hasSubscription')
            ->willReturnMap($quoteItemHasSubscriptionMap);

        $this->assertEquals(
            $hasSubscription,
            $this->quoteHelper->hasSubscription($quoteMock)
        );
    }

    /**
     * @return array
     */
    public static function hasSubscriptionDataProvider()
    {
        return [
            'Empty items' => [
                'itemCount' => 0,
                'hasSubscriptionItemResults' => null,
                'hasSubscription' => false
            ],
            'Items don\'t have subscription' => [
                'itemCount' => 2,
                'hasSubscriptionItemResults' => [false, false],
                'hasSubscription' => false
            ],
            'One of items has subscription' => [
                'itemCount' => 3,
                'hasSubscriptionItemResults' => [false, false, true],
                'hasSubscription' => true
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(QuoteModel::class)->disableOriginalConstructor()->getMock();
    }
}
