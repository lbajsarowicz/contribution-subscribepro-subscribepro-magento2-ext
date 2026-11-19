<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\SalesRule;

class RuleAddress extends \Magento\Quote\Model\Quote\Address
{
    /**
     * @return string|null
     */
    public function getAppliedRuleIds()
    {
        return $this->getData('applied_rule_ids');
    }

    /**
     * @return array|null
     */
    public function getDiscountDescriptionArray()
    {
        return $this->getData('discount_description_array');
    }

    /**
     * @param array $discountDescriptions
     * @return $this
     */
    public function setDiscountDescriptionArray($discountDescriptions)
    {
        $this->_data['discount_description_array'] = $discountDescriptions;
        return $this;
    }
}
