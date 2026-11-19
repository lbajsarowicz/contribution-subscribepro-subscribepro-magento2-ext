<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

class SubscriptionQuoteItem extends \Swarming\SubscribePro\Test\Unit\Quote\Item
{
    public function getIsVirtual()
    {
        return $this->getData('is_virtual');
    }
}
