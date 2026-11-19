<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Checkout;

class CustomerQuote extends \Magento\Quote\Model\Quote
{
    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }
}
