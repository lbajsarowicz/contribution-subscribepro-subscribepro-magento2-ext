<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook\Handler\PaymentProfile;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\Vault;
use Swarming\SubscribePro\Platform\Service\PaymentProfile;
use Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\CreateHandler;

class CreateHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider executeDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('executeDataProvider')]
    public function testExecute($profileData, $customerData, $email, $profileHasCustomerId, $existing, $methodCode)
    {
        $customerId = 4341;
        $tokenManagement = $this->createMock(PaymentTokenManagementInterface::class);
        $tokenRepository = $this->createMock(PaymentTokenRepositoryInterface::class);
        $customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $profileService = $this->getMockBuilder(PaymentProfile::class)->disableOriginalConstructor()->getMock();
        $vault = $this->getMockBuilder(Vault::class)->disableOriginalConstructor()->getMock();
        $tokenFactory = $this->getMockBuilder(CreditCardTokenFactory::class)
            ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $token = $this->createMock(PaymentTokenInterface::class);
        $event = $this->createMock(EventInterface::class);
        $event->method('getEventData')->willReturnMap([
            ['payment_profile', $profileData],
            ['customer', $customerData],
            ['payment_profile_id', 123]
        ]);

        if ($email !== null) {
            $customer = $this->createMock(CustomerInterface::class);
            $customer->method('getId')->willReturn($customerId);
            $customerRepository->expects($this->exactly($profileHasCustomerId || $existing ? 1 : 2))
                ->method('get')->with($email)->willReturn($customer);
        } else {
            $customerRepository->expects($this->never())->method('get');
        }
        $tokenManagement->expects($this->once())->method('getByGatewayToken')
            ->with(123, $methodCode, $customerId)->willReturn($existing ? $token : null);
        $tokenFactory->expects($existing ? $this->never() : $this->once())
            ->method('create')->willReturn($token);
        $tokenRepository->expects($existing ? $this->never() : $this->once())->method('save')->with($token);
        $vault->expects($existing ? $this->never() : $this->once())
            ->method('initVault')->with($token, $this->isInstanceOf(PaymentProfileInterface::class), $methodCode);

        if ($existing) {
            $profileService->expects($this->never())->method('createProfile');
        } else {
            $profile = $this->createMock(PaymentProfileInterface::class);
            $profile->expects($this->once())->method('getMagentoCustomerId')
                ->willReturn($profileHasCustomerId ? $customerId : null);
            $profile->expects($profileHasCustomerId ? $this->never() : $this->once())
                ->method('setMagentoCustomerId')->with($customerId);
            $profileService->expects($this->once())->method('createProfile')->with($profileData)->willReturn($profile);
        }

        $handler = new CreateHandler(
            $tokenManagement,
            $tokenRepository,
            $customerRepository,
            $profileService,
            $vault,
            $tokenFactory
        );
        $handler->execute($event);
    }

    public static function executeDataProvider()
    {
        $profile = [PaymentProfileInterface::MAGENTO_CUSTOMER_ID => 4341];
        $customer = [PlatformCustomerInterface::MAGENTO_CUSTOMER_ID => 4341];
        return [
            'Customer ID in profile' => [$profile, [], null, false, false, ConfigProvider::CODE],
            'Customer ID in customer' => [[], $customer, null, false, false, ConfigProvider::CODE],
            'Email in profile' => [
                [PaymentProfileInterface::CUSTOMER_EMAIL => 'profile@example.com'],
                [], 'profile@example.com', false, false, ConfigProvider::CODE
            ],
            'Email in customer' => [
                [], [PlatformCustomerInterface::EMAIL => 'customer@example.com'],
                'customer@example.com', false, false, ConfigProvider::CODE
            ],
            'Profile already has customer ID' => [$profile, [], null, true, false, ConfigProvider::CODE],
            'Existing token' => [$profile, [], null, true, true, ConfigProvider::CODE],
            'Apple Pay token' => [
                $profile + ['payment_method_type' => CreateHandler::PAYMENT_METHOD_CODE],
                [], null, true, false, ApplePayConfigProvider::CODE
            ]
        ];
    }
}
