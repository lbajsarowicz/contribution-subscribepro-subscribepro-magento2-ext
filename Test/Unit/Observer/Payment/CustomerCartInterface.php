<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Payment;

interface CustomerCartInterface extends \Magento\Quote\Api\Data\CartInterface
{
    /**
     * @return int|null
     */
    public function getCustomerId();
}
