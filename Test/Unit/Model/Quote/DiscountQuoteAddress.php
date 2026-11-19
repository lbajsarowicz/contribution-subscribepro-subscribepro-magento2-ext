<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

class DiscountQuoteAddress extends \Magento\Quote\Model\Quote\Address
{
    public function getDiscountDescriptionArray()
    {
        return $this->getData('discount_description_array');
    }

    public function setDiscountDescriptionArray($value)
    {
        return $this->setData('discount_description_array', $value);
    }
}
