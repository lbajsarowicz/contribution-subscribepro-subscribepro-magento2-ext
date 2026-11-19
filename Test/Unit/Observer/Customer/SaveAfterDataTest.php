<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use Swarming\SubscribePro\Model\Config\General;
use Swarming\SubscribePro\Observer\Customer\SaveAfterData;
use Swarming\SubscribePro\Platform\Manager\Customer as CustomerManager;
use Swarming\SubscribePro\Platform\Service\Customer as CustomerService;

class SaveAfterDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider executeDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('executeDataProvider')]
    public function testExecute($enabled, $unchanged, $found, $failure, $applePay)
    {
        $config = $this->getMockBuilder(General::class)->disableOriginalConstructor()->getMock();
        $manager = $this->getMockBuilder(CustomerManager::class)->disableOriginalConstructor()->getMock();
        $service = $this->getMockBuilder(CustomerService::class)->disableOriginalConstructor()->getMock();
        $logger = $this->createMock(LoggerInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn(123);
        $customer->method('getWebsiteId')->willReturn(12);
        $customer->method('getFirstname')->willReturn('First');
        $customer->method('getLastname')->willReturn('Last');
        $customer->method('getEmail')->willReturn('customer@example.com');
        $customer->method('getGroupId')->willReturn(3);
        $observer = new Observer([
            'customer_data_object' => $customer,
            'orig_customer_data_object' => $unchanged ? $customer : null
        ]);
        $config->expects($this->once())->method('isEnabled')->with(12)->willReturn($enabled);
        $synchronize = $enabled && !$unchanged;
        $config->expects($synchronize ? $this->once() : $this->never())
            ->method('isApplePayEnabled')->with(12)->willReturn($applePay);
        $platformCustomer = $this->createMock(PlatformCustomerInterface::class);
        $exception = new \RuntimeException('Synchronization failed.');
        $load = $manager->expects($synchronize ? $this->once() : $this->never())
            ->method('getCustomerByMagentoCustomerId')->with(123, $applePay, 12);
        if ($failure === 'load') {
            $load->willThrowException($exception);
        } else {
            $load->willReturn($found ? $platformCustomer : null);
        }
        $save = $synchronize && $found && $failure !== 'load';
        $platformCustomer->expects($save ? $this->once() : $this->never())
            ->method('setFirstName')->with('First');
        $platformCustomer->expects($save ? $this->once() : $this->never())
            ->method('setLastName')->with('Last');
        $platformCustomer->expects($save ? $this->once() : $this->never())
            ->method('setEmail')->with('customer@example.com');
        $platformCustomer->expects($save ? $this->once() : $this->never())
            ->method('setMagentoCustomerGroupId')->with(3);
        $persist = $service->expects($save ? $this->once() : $this->never())
            ->method('saveCustomer')->with($platformCustomer, 12);
        if ($failure === 'save') {
            $persist->willThrowException($exception);
        }
        $logger->expects($synchronize && $failure ? $this->once() : $this->never())
            ->method('critical')->with($exception);
        $handler = new SaveAfterData($config, $manager, $service, $logger);
        $handler->execute($observer);
    }

    public static function executeDataProvider()
    {
        return [
            'Disabled' => [false, false, true, null, false],
            'Unchanged' => [true, true, true, null, false],
            'Customer not found' => [true, false, false, null, false],
            'Load exception' => [true, false, false, 'load', false],
            'Save exception' => [true, false, true, 'save', false],
            'Save' => [true, false, true, null, false],
            'Apple Pay creates customer' => [true, false, true, null, true]
        ];
    }
}
