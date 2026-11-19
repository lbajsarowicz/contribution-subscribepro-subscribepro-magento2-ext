<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote\QuoteItem;

interface SubscriptionQuoteInterface extends \Magento\Quote\Api\Data\CartInterface
{
    public function getStore();

    public function getCouponCode();
}
