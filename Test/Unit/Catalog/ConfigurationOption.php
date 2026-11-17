<?php

namespace Swarming\SubscribePro\Test\Unit\Catalog;

class ConfigurationOption extends \Magento\Catalog\Model\Product\Configuration\Item\Option
{
    public function getProduct()
    {
        return $this->getData('product');
    }
}
