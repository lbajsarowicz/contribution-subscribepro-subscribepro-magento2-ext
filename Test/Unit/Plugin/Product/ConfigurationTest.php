<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Product\Configuration as ProductConfiguration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface as ConfigurationItemInterface;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Swarming\SubscribePro\Plugin\Product\Configuration;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Product\Configuration
     */
    protected $configurationPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Product
     */
    protected $productHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp(): void
    {
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->configurationPlugin = new Configuration(
            $this->productHelperMock,
            $this->quoteItemHelperMock
        );
    }

    public function testAroundGetCustomOptionsIfSubscribeProNotEnabled()
    {
        $subjectResult = ['result'];
        $subjectMock = $this->createProductConfigurationMock();
        $productMock = $this->createProductMock();

        $configurationItemMock = $this->createProductConfigurationItemMock();
        $configurationItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $proceed = $this->createProceedCallback($subjectResult);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(false);

        $this->assertEquals(
            $subjectResult,
            $this->configurationPlugin->aroundGetCustomOptions($subjectMock, $proceed, $configurationItemMock)
        );
    }

    /**
     * @param array $subjectResult
     * @param bool|null $createNewSubscription
     * @param string $subscriptionInterval
     * @param array $result
     * @dataProvider aroundGetCustomOptionsDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('aroundGetCustomOptionsDataProvider')]
    public function testAroundGetCustomOptions($subjectResult, $createNewSubscription, $subscriptionInterval, $result)
    {
        $subjectMock = $this->createProductConfigurationMock();
        $productMock = $this->createProductMock();

        $configurationItemMock = $this->createProductConfigurationItemMock();
        $configurationItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $proceed = $this->createProceedCallback($subjectResult);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getCreateNewSubscriptionAtCheckout')
            ->with($configurationItemMock)
            ->willReturn($createNewSubscription);
        $this->quoteItemHelperMock->expects($createNewSubscription ? $this->once() : $this->never())
            ->method('getSubscriptionInterval')
            ->with($configurationItemMock)
            ->willReturn($subscriptionInterval);

        $this->assertEquals(
            $result,
            $this->configurationPlugin->aroundGetCustomOptions($subjectMock, $proceed, $configurationItemMock)
        );
    }

    /**
     * @return array
     */
    public static function aroundGetCustomOptionsDataProvider()
    {
        return [
            'Unset subscription flag:one time delivery' => [
                'subjectResult' => ['key' => 'value'],
                'createNewSubscription' => null,
                'subscriptionInterval' => null,
                'result' => [
                    'key' => 'value',
                    ['label' => 'Delivery', 'value' => 'One Time']
                ]
            ],
            'Onetime purchase subscription option:return merged result' => [
                'subjectResult' => [
                    ['label' => 'some_label', 'value' => 'some_value']
                ],
                'createNewSubscription' => false,
                'subscriptionInterval' => 'interval',
                'result' => [
                    ['label' => 'some_label', 'value' => 'some_value'],
                    ['label' => (string)__('Delivery'), 'value' => (string)__('One Time')],
                ]
            ],
            'Subscribe subscription option:return merged result' => [
                'subjectResult' => [
                    ['label' => 'key', 'value' => 'val']
                ],
                'createNewSubscription' => true,
                'subscriptionInterval' => 'monthly',
                'result' => [
                    ['label' => 'key', 'value' => 'val'],
                    ['label' => (string)__('Regular Delivery'), 'value' => (string)__('monthly')],
                ]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface
     */
    private function createProductConfigurationItemMock()
    {
        return $this->getMockBuilder(ConfigurationItemInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Helper\Product\Configuration
     */
    private function createProductConfigurationMock()
    {
        return $this->getMockBuilder(ProductConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $result
     * @return callable
     */
    private function createProceedCallback(array $result)
    {
        return function ($item) use ($result) {
            return $result;
        };
    }
}
