<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Checkout;

class CheckoutSession extends \Magento\Checkout\Model\Session
{
    /**
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        $this->storage->setData($key, $value);
        return $this;
    }
}
