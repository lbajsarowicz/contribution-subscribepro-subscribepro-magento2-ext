<?php

namespace Swarming\SubscribePro\Test\Unit\Platform;

class Sdk extends \SubscribePro\Sdk
{
    public function getWebhookService()
    {
        return $this->getService('webhook');
    }
}
