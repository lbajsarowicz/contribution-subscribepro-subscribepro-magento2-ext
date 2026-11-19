<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config\Source;

use Swarming\SubscribePro\Model\Config\Source\PaymentAction;

class PaymentActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\PaymentAction
     */
    protected $paymentActionSource;

    protected function setUp(): void
    {
        $this->paymentActionSource = new PaymentAction();
    }

    public function testToOptionArray()
    {
        $result = $this->paymentActionSource->toOptionArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $randomEl = $result[array_rand($result)];
        $this->assertIsArray($randomEl);
        $this->assertArrayHasKey('value', $randomEl);
        $this->assertArrayHasKey('label', $randomEl);
    }
}
