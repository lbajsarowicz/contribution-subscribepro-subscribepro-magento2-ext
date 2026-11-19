<?php

namespace Swarming\SubscribePro\Test\Unit;

class QuoteItemOption extends \Magento\Quote\Model\Quote\Item\Option
{
    public function getCode()
    {
        return $this->getData('code');
    }

    public function setCode($value)
    {
        return $this->setData('code', $value);
    }

    public function setValue($value)
    {
        return $this->setData('value', $value);
    }
}
