<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

interface SubscriptionQuoteInterface extends \Magento\Quote\Api\Data\CartInterface
{
    public function getCustomerId();

    public function getAllShippingAddresses();

    public function getAllVisibleItems();
}
