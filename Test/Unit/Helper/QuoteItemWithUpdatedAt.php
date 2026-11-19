<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

class QuoteItemWithUpdatedAt extends \Magento\Quote\Model\Quote\Item
{
    public function setUpdatedAt($value)
    {
        return $this->setData('updated_at', $value);
    }
}
