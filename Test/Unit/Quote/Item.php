<?php

namespace Swarming\SubscribePro\Test\Unit\Quote;

class Item extends \Magento\Quote\Model\Quote\Item
{
    public function getItemId()
    {
        return $this->getData('item_id');
    }

    public function getParentItemId()
    {
        return $this->getData('parent_item_id');
    }
}
