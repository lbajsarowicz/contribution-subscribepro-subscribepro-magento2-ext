<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Swarming\SubscribePro\Helper\DiscountRule;
use Swarming\SubscribePro\Model\Rule\Condition\Base as ConditionBase;

class DiscountRuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountRule
     */
    protected $discountRuleHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->discountRuleHelper = new DiscountRule(
            $this->loggerMock
        );
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @dataProvider getIsNewSubscriptionDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getIsNewSubscriptionDataProvider')]
    public function testIsNewSubscription($expected, $subscriptionParams)
    {
        $this->assertEquals($expected, $this->discountRuleHelper->isNewSubscription($subscriptionParams));
    }

    /**
     * @return array
     */
    public static function getIsNewSubscriptionDataProvider()
    {
        return [
            'Without subscription params' => [
                'expected' => false,
                'subscriptionParams' => [],
            ],
            'With subscription params, nonsubscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With subscription params, new subscription' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => 'One Month',
                    "create_new_subscription_at_checkout" => true,
                ],
            ],
            'With subscription params, fulfilling' => [
                'expected' => false,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "item_fulfils_subscription" => true,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @dataProvider getIsItemFulfilsSubscriptionDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getIsItemFulfilsSubscriptionDataProvider')]
    public function testIsItemFulfilsSubscription($expected, $subscriptionParams)
    {
        $this->assertEquals($expected, $this->discountRuleHelper->isItemFulfilsSubscription($subscriptionParams));
    }

    /**
     * @return array
     */
    public static function getIsItemFulfilsSubscriptionDataProvider()
    {
        return [
            'Without subscription params' => [
                'expected' => false,
                'subscriptionParams' => [],
            ],
            'With subscription params, nonsubscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With subscription params, new subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With subscription params, fulfilling' => [
                'expected' => true,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "item_fulfils_subscription" => true,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @dataProvider getIsItemNewOrFulfillingSubscriptionDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getIsItemNewOrFulfillingSubscriptionDataProvider')]
    public function testIsItemNewOrFulfillingSubscription($expected, $subscriptionParams)
    {
        $this->assertEquals(
            $expected,
            $this->discountRuleHelper->isItemNewOrFulfillingSubscription($subscriptionParams)
        );
    }

    /**
     * @return array
     */
    public static function getIsItemNewOrFulfillingSubscriptionDataProvider()
    {
        return [
            'Without subscription params' => [
                'expected' => false,
                'subscriptionParams' => [],
            ],
            'With subscription params, nonsubscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With subscription params, new subscription' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
            ],
            'With subscription params, fulfilling' => [
                'expected' => true,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "item_fulfils_subscription" => true,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @dataProvider getGetSubscriptionOptionsDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getGetSubscriptionOptionsDataProvider')]
    public function testGetSubscriptionOptions($expected, $subscriptionParams)
    {
        $this->assertEquals($expected, $this->discountRuleHelper->getSubscriptionOptions($subscriptionParams));
    }

    /**
     * @return array
     */
    public static function getGetSubscriptionOptionsDataProvider()
    {
        return [
            'Without params' => [
                'expected' => [
                    'new_subscription' => false,
                    'is_fulfilling' => false,
                    'reorder_ordinal' => false,
                    'interval' => false,
                ],
                'subscriptionParams' => [],
            ],
            'With params, one-time purchase' => [
                'expected' => [
                    'new_subscription' => false,
                    'is_fulfilling' => false,
                    'reorder_ordinal' => false,
                    'interval' => false,
                ],
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With params, new subscription' => [
                'expected' => [
                    'new_subscription' => true,
                    'is_fulfilling' => false,
                    'reorder_ordinal' => 0,
                    'interval' => 'One Month',
                ],
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
            ],
            'With subscription params, fulfilling' => [
                'expected' => [
                    'new_subscription' => false,
                    'is_fulfilling' => true,
                    'reorder_ordinal' => 2,
                    'interval' => 'One Month',
                ],
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @dataProvider getValidateReorderOrdinalDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getValidateReorderOrdinalDataProvider')]
    public function testValidateReorderOrdinal($expected, $subscriptionParams)
    {
        $this->assertEquals($expected, $this->discountRuleHelper->validateReorderOrdinal($subscriptionParams));
    }

    /**
     * @return array
     */
    public static function getValidateReorderOrdinalDataProvider()
    {
        return [
            'Without params' => [
                'expected' => false,
                'subscriptionParams' => [],
            ],
            'Subscription Options are false' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => false,
                    "interval" => false,
                    "reorder_ordinal" => false,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'Non subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With params, new subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
            ],
            'With subscription params, fulfilling' => [
                'expected' => 2,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @dataProvider getValidateIntervalDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getValidateIntervalDataProvider')]
    public function testValidateInterval($expected, $subscriptionParams)
    {
        $this->assertEquals($expected, $this->discountRuleHelper->validateInterval($subscriptionParams));
    }

    /**
     * @return array
     */
    public static function getValidateIntervalDataProvider()
    {
        return [
            'Without params' => [
                'expected' => false,
                'subscriptionParams' => [],
            ],
            'Subscription Options are false' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => false,
                    "interval" => false,
                    "reorder_ordinal" => false,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'Non subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
            ],
            'With params, new subscription' => [
                'expected' => 'One Month',
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
            ],
            'With subscription params, fulfilling' => [
                'expected' => 'One Month',
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param array $subscriptionParams
     * @param int $conditionValue
     * @param string $op
     * @dataProvider getValidateStatusDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getValidateStatusDataProvider')]
    public function testValidateStatus($expected, $subscriptionParams, $conditionValue, $op)
    {
        $this->assertEquals(
            $expected,
            $this->discountRuleHelper->validateStatus($subscriptionParams, $conditionValue, $op)
        );
    }

    /**
     * @return array
     */
    public static function getValidateStatusDataProvider()
    {
        return [
            'Without params, applies to any subscription' => [
                'expected' => false,
                'subscriptionParams' => [],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '==',
            ],
            'Subscription Options are false, applies to any subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => false,
                    "interval" => false,
                    "reorder_ordinal" => false,
                    "create_new_subscription_at_checkout" => false,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '==',
            ],
            'Subscription Options are false, applies to any subscription, negated result' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => false,
                    "interval" => false,
                    "reorder_ordinal" => false,
                    "create_new_subscription_at_checkout" => false,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '!=',
            ],
            'Non subscription, applies to any subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '==',
            ],
            'Non subscription, applies to any subscription, negated result' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '!=',
            ],
            'Non subscription, applies to new subscription, negated result' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_NEW,
                'op' => '!=',
            ],
            'Non subscription, applies to recurring subscription, negated result' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "onetime_purchase",
                    "interval" => null,
                    "create_new_subscription_at_checkout" => false,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_REORDER,
                'op' => '!=',
            ],
            'With params, new subscription, applies to any subscription' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '==',
            ],
            'With params, new subscription, applies to new subscription' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_NEW,
                'op' => '==',
            ],
            'With params, new subscription, applies to recurring subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_REORDER,
                'op' => '==',
            ],
            'With params, new subscription, applies to any subscription, negated result' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '!=',
            ],
            'With params, new subscription, applies to new subscription, negated result' => [
                'expected' => false,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_NEW,
                'op' => '!=',
            ],
            'With params, new subscription, applies to recurring subscription, negated result' => [
                'expected' => true,
                'subscriptionParams' => [
                    "option" => "subscription",
                    "interval" => "One Month",
                    "create_new_subscription_at_checkout" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_REORDER,
                'op' => '!=',
            ],
            'With subscription params, fulfilling, applies to any subscription' => [
                'expected' => true,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '==',
            ],
            'With subscription params, fulfilling, applies to new subscription' => [
                'expected' => false,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_NEW,
                'op' => '==',
            ],
            'With subscription params, fulfilling, applies to recurring subscription' => [
                'expected' => true,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_REORDER,
                'op' => '==',
            ],
            'With subscription params, fulfilling, applies to any subscription, negated result' => [
                'expected' => false,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_ANY,
                'op' => '!=',
            ],
            'With subscription params, fulfilling, applies to new subscription, negated result' => [
                'expected' => true,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_NEW,
                'op' => '!=',
            ],
            'With subscription params, fulfilling, applies to recurring subscription, negated result' => [
                'expected' => false,
                'subscriptionParams' => [
                    "interval" => 'One Month',
                    "reorder_ordinal" => 2,
                    "item_fulfils_subscription" => true,
                ],
                'conditionValue' => ConditionBase::SUBSCRIPTION_STATUS_REORDER,
                'op' => '!=',
            ],
        ];
    }
}
