<?php

namespace Swarming\SubscribePro\Test\Unit\Catalog;

class Product extends \Magento\Catalog\Model\Product
{
    public function getParentItemId()
    {
        return $this->getData('parent_item_id');
    }

    public function hasCustomerGroupId()
    {
        return $this->hasData('customer_group_id');
    }

    public function getCustomerGroupId()
    {
        return $this->getData('customer_group_id');
    }
}
