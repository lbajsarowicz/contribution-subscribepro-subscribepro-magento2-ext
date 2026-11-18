<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote\SubscriptionOption;

interface SubscriptionOptionExtensionAttributes extends \Magento\Quote\Api\Data\ProductOptionExtensionInterface
{
    /**
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface|null
     */
    public function getSubscriptionOption();

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface $subscriptionOption
     * @return $this
     */
    public function setSubscriptionOption($subscriptionOption);
}
