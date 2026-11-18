<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount as SubscriptionDiscountConfig;
use Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;

class ItemSubscriptionDiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount
     */
    protected $itemSubscriptionDiscount;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrencyMock;

    protected function setUp(): void
    {
        $this->subscriptionDiscountConfigMock = $this->getMockBuilder(SubscriptionDiscountConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)->getMock();

        $this->itemSubscriptionDiscount = new ItemSubscriptionDiscount(
            $this->subscriptionDiscountConfigMock,
            $this->platformProductManagerMock,
            $this->quoteItemHelperMock,
            $this->priceCurrencyMock,
            new \Swarming\SubscribePro\Helper\SalesRuleValidator()
        );
    }

    /**
     * @param int $storeId
     * @param string $productSku
     * @param float $itemBasePrice
     * @param float $baseCartDiscount
     * @param bool $isDiscountPercentage
     * @param float $discount
     * @param float $baseSubscriptionDiscount
     * @param int $qty
     * @param string $cartRuleCombineType
     * @dataProvider processSubscriptionDiscountIfNotAppliedSubscriptionDiscountDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('processSubscriptionDiscountIfNotAppliedSubscriptionDiscountDataProvider')]
    public function testProcessSubscriptionDiscountIfNotAppliedSubscriptionDiscount(
        $storeId,
        $productSku,
        $itemBasePrice,
        $baseCartDiscount,
        $isDiscountPercentage,
        $discount,
        $baseSubscriptionDiscount,
        $qty,
        $cartRuleCombineType
    ) {
        $rollbackCallback = $this->createCallbackMock();

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getIsDiscountPercentage')
            ->willReturn($isDiscountPercentage);
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($discount);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->exactly(2))->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getBaseDiscountAmount')->willReturn($baseCartDiscount);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);
        $quoteItemMock->expects($this->never())->method('setDiscountAmount');
        $quoteItemMock->expects($this->never())->method('setBaseDiscountAmount');

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku, 1)
            ->willReturn($platformProductMock);

        $this->subscriptionDiscountConfigMock->expects($this->exactly(2))
            ->method('getCartRuleCombineType')
            ->with($storeId)
            ->willReturn($cartRuleCombineType);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($baseSubscriptionDiscount, $storeId)
            ->willReturn($baseSubscriptionDiscount);

        $this->itemSubscriptionDiscount->processSubscriptionDiscount($quoteItemMock, $itemBasePrice, $rollbackCallback);
    }

    /**
     * @return array
     */
    public static function processSubscriptionDiscountIfNotAppliedSubscriptionDiscountDataProvider()
    {
        return [
            'Card combine type apply greatest:base subscription discount < base cart discount' => [
                'storeId' => 123123,
                'productSku' => 'sku_sku',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 20,
                'isDiscountPercentage' => true,
                'discount' => 0.1,
                'baseSubscriptionDiscount' => 10,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_GREATEST,
            ],
            'Card combine type apply least:base subscription discount > base cart discount' => [
                'storeId' => 2421,
                'productSku' => 'sku',
                'itemBasePrice' => 10,
                'baseCartDiscount' => 0.5,
                'isDiscountPercentage' => false,
                'discount' => 2,
                'baseSubscriptionDiscount' => 2,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_LEAST,
            ],
            'Card combine type apply cart discount:cart rules applied' => [
                'storeId' => 3232,
                'productSku' => 'sku2',
                'itemBasePrice' => 15,
                'baseCartDiscount' => 2,
                'isDiscountPercentage' => false,
                'discount' => 5,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_CART_DISCOUNT,
            ],
        ];
    }

    /**
     * @param int $storeId
     * @param string $productSku
     * @param float $itemBasePrice
     * @param float $baseCartDiscount
     * @param bool $isDiscountPercentage
     * @param float $discount
     * @param float $baseSubscriptionDiscount
     * @param int $qty
     * @param string $cartRuleCombineType
     * @param float $subscriptionDiscount
     * @param array $discountDescriptions
     * @dataProvider processSubscriptionDiscountIfOnlySubscriptionDiscountDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('processSubscriptionDiscountIfOnlySubscriptionDiscountDataProvider')]
    public function testProcessSubscriptionDiscountIfOnlySubscriptionDiscount(
        $storeId,
        $productSku,
        $itemBasePrice,
        $baseCartDiscount,
        $isDiscountPercentage,
        $discount,
        $baseSubscriptionDiscount,
        $qty,
        $cartRuleCombineType,
        $subscriptionDiscount,
        $discountDescriptions
    ) {
        $rollbackCallback = $this->createCallbackMock();
        $rollbackCallback->expects($this->once())->method('__invoke');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getIsDiscountPercentage')
            ->willReturn($isDiscountPercentage);
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($discount);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getDiscountDescriptionArray')->willReturn($discountDescriptions);
        $addressMock->expects($this->once())->method('setDiscountDescriptionArray')->with($discountDescriptions);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->exactly(2))->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getBaseDiscountAmount')->willReturn($baseCartDiscount);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);
        $quoteItemMock->method('getAddress')->willReturn($addressMock);
        $quoteItemMock->expects($this->once())->method('setDiscountAmount')->with($subscriptionDiscount);
        $quoteItemMock->expects($this->once())->method('setBaseDiscountAmount')->with($subscriptionDiscount);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku, 1)
            ->willReturn($platformProductMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('getCartRuleCombineType')
            ->with($storeId)
            ->willReturn($cartRuleCombineType);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($baseSubscriptionDiscount, $storeId)
            ->willReturn($subscriptionDiscount);

        $this->itemSubscriptionDiscount->processSubscriptionDiscount($quoteItemMock, $itemBasePrice, $rollbackCallback);
    }

    /**
     * @return array
     */
    public static function processSubscriptionDiscountIfOnlySubscriptionDiscountDataProvider()
    {
        return [
            'Card combine type apply greatest:base subscription discount > base cart discount' => [
                'storeId' => 123123,
                'productSku' => 'sku_sku',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 5,
                'isDiscountPercentage' => true,
                'discount' => 0.1,
                'baseSubscriptionDiscount' => 10,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_GREATEST,
                'subscriptionDiscount' => 10,
                'discountDescriptions' => ['key' => 'value'],
            ],
            'Card combine type apply greatest:base subscription discount = base cart discount' => [
                'storeId' => 5654,
                'productSku' => 'product_sku',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 10,
                'isDiscountPercentage' => true,
                'discount' => 0.05,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_GREATEST,
                'subscriptionDiscount' => 20,
                'discountDescriptions' => [],
            ],
            'Card combine type apply least:base subscription discount = base cart discount' => [
                'storeId' => 6485,
                'productSku' => 'sku323',
                'itemBasePrice' => 10,
                'baseCartDiscount' => 2,
                'isDiscountPercentage' => false,
                'discount' => 2,
                'baseSubscriptionDiscount' => 2,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_LEAST,
                'subscriptionDiscount' => 2,
                'discountDescriptions' => ['some' => 'description'],
            ],
            'Card combine type apply least:base subscription discount < base cart discount' => [
                'storeId' => 123343,
                'productSku' => '22sku323',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 15,
                'isDiscountPercentage' => true,
                'discount' => 0.05,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_LEAST,
                'subscriptionDiscount' => 10,
                'discountDescriptions' => ['another' => 'description'],
            ],
            'Card combine type apply cart discount:cart rules applied' => [
                'storeId' => 3232,
                'productSku' => 'sku2',
                'itemBasePrice' => 50,
                'baseCartDiscount' => 0,
                'isDiscountPercentage' => false,
                'discount' => 5,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_CART_DISCOUNT,
                'subscriptionDiscount' => 20,
                'discountDescriptions' => ['key' => 'value'],
            ],
            'Card combine type apply subscription' => [
                'storeId' => 43252,
                'productSku' => 'skusku',
                'itemBasePrice' => 80,
                'baseCartDiscount' => 12,
                'isDiscountPercentage' => false,
                'discount' => 10,
                'baseSubscriptionDiscount' => 10,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_SUBSCRIPTION,
                'subscriptionDiscount' => 30,
                'discountDescriptions' => ['address' => 'description'],
            ],
        ];
    }

    public function testProcessSubscriptionDiscountIfCombineDiscounts()
    {
        $storeId = 54231;
        $productSku = 'productSku';
        $itemBasePrice = 100;
        $baseCartDiscount = 10;
        $isDiscountPercentage = true;
        $discount = 0.2;
        $baseSubscriptionDiscount = 40;
        $qty = 2;
        $subscriptionDiscount = 45;
        $discountDescriptions = ['discount' => 'description'];
        $oldDiscountAmount = 5;
        $oldBaseDiscountAmount = 2;
        $newDiscountAmount = 50;
        $newBaseDiscountAmount = 47;

        $rollbackCallback = $this->createCallbackMock();
        $rollbackCallback->expects($this->never())->method('__invoke');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getIsDiscountPercentage')
            ->willReturn($isDiscountPercentage);
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($discount);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getDiscountDescriptionArray')->willReturn($discountDescriptions);
        $addressMock->expects($this->once())->method('setDiscountDescriptionArray')->with($discountDescriptions);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->exactly(2))->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->exactly(2))->method('getBaseDiscountAmount')
            ->willReturnOnConsecutiveCalls($baseCartDiscount, $oldBaseDiscountAmount);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);
        $quoteItemMock->method('getAddress')->willReturn($addressMock);
        $quoteItemMock->expects($this->once())->method('getDiscountAmount')->willReturn($oldDiscountAmount);
        $quoteItemMock->expects($this->once())->method('setDiscountAmount')->with($newDiscountAmount);
        $quoteItemMock->expects($this->once())->method('setBaseDiscountAmount')->with($newBaseDiscountAmount);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku, 1)
            ->willReturn($platformProductMock);

        $this->subscriptionDiscountConfigMock->expects($this->exactly(2))
            ->method('getCartRuleCombineType')
            ->with($storeId)
            ->willReturn(CartRuleCombine::TYPE_COMBINE_SUBSCRIPTION);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($baseSubscriptionDiscount, $storeId)
            ->willReturn($subscriptionDiscount);

        $this->itemSubscriptionDiscount->processSubscriptionDiscount($quoteItemMock, $itemBasePrice, $rollbackCallback);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(DiscountQuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getDiscountAmount',
                'getBaseDiscountAmount',
                'getQuote',
                'getProduct',
                'getAddress',
                'getQty',
                'setDiscountAmount',
                'setBaseDiscountAmount',
                '__wakeup'
            ])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    private function createAddressMock()
    {
        return $this->getMockBuilder(DiscountQuoteAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setDiscountDescriptionArray', 'getDiscountDescriptionArray', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|callable
     */
    private function createCallbackMock()
    {
        return $this->getMockBuilder(DiscountRollback::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(PlatformProductInterface::class)->getMock();
    }
}
