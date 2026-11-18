<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

class DiscountQuoteItem extends \Magento\Quote\Model\Quote\Item
{
    public function getDiscountAmount()
    {
        return $this->getData('discount_amount');
    }

    public function setDiscountAmount($value)
    {
        return $this->setData('discount_amount', $value);
    }

    public function getBaseDiscountAmount()
    {
        return $this->getData('base_discount_amount');
    }

    public function setBaseDiscountAmount($value)
    {
        return $this->setData('base_discount_amount', $value);
    }
}
