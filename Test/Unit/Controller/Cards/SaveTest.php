<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Cards;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Controller\Cards\Save;
use Swarming\SubscribePro\Gateway\Command\AuthorizeCommand as WalletAuthorizeCommand;
use Swarming\SubscribePro\Gateway\Command\VoidCommand;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Swarming\SubscribePro\Gateway\Config\VaultConfig;
use Swarming\SubscribePro\Model\Vault\Form as VaultForm;
use Swarming\SubscribePro\Model\Vault\Validator as VaultFormValidator;

class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Cards\Save
     */
    protected $saveController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Model\Session
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Vault\Form
     */
    protected $vaultFormMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $platformVaultConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Vault\Validator
     */
    protected $vaultFormValidator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Command\AuthorizeCommand
     */
    protected $walletAuthorizeCommand;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Message\ManagerInterface
     */
    protected $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Request\Http
     */
    protected $requestMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam', 'getParams', 'isPost'])
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->getMock();

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactoryMock);
        $contextMock->expects($this->once())->method('getMessageManager')->willReturn($this->messageManagerMock);

        $this->formKeyValidatorMock = $this->getMockBuilder(FormKeyValidator::class)
            ->disableOriginalConstructor()->getMock();
        $this->vaultFormMock = $this->getMockBuilder(VaultForm::class)
            ->disableOriginalConstructor()->getMock();
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformVaultConfigMock = $this->getMockBuilder(VaultConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->gatewayConfig = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->vaultFormValidator = $this->getMockBuilder(VaultFormValidator::class)
            ->disableOriginalConstructor()->getMock();
        $this->walletAuthorizeCommand = $this->getMockBuilder(WalletAuthorizeCommand::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->saveController = new Save(
            $contextMock,
            $this->formKeyValidatorMock,
            $this->customerSessionMock,
            $this->vaultFormMock,
            $this->platformVaultConfigMock,
            $this->gatewayConfig,
            $this->vaultFormValidator,
            $this->walletAuthorizeCommand,
            $this->getMockBuilder(VoidCommand::class)->disableOriginalConstructor()->getMock(),
            $this->storeManager
        );
    }

    /**
     * @param bool $isFormValid
     * @param bool $isPost
     * @param bool $isActiveVaultConfig
     * @dataProvider executeIfBadRequestParamsDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('executeIfBadRequestParamsDataProvider')]
    public function testExecuteIfBadRequestParams($isFormValid, $isPost, $isActiveVaultConfig)
    {
        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'failed'])
            ->willReturnSelf();

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn($isFormValid);

        $this->requestMock
            ->method('isPost')
            ->willReturn($isPost);

        $this->platformVaultConfigMock
            ->method('isActive')
            ->willReturn($isActiveVaultConfig);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    /**
     * @return array
     */
    public static function executeIfBadRequestParamsDataProvider()
    {
        return [
            'Form is not valid' => [
                'isFormValid' => false,
                'isPost' => true,
                'isActiveVaultConfig' => true,
            ],
            'Request is not post' => [
                'isFormValid' => true,
                'isPost' => false,
                'isActiveVaultConfig' => true,
            ],
            'Vault config is not active' => [
                'isFormValid' => true,
                'isPost' => true,
                'isActiveVaultConfig' => false,
            ]
        ];
    }

    public function testExecuteIfFailToCreateProfile()
    {
        $message = 'error';
        $exception = new LocalizedException(__($message));

        $params = ['request params'];
        $customerId = 123;

        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'failed'])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with($params, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfFailToCreateProfileWithGeneralException()
    {
        $exception = new \InvalidArgumentException('error message');

        $params = ['request params'];
        $customerId = 53445;

        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'failed'])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with($params, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, __('An error occurred while saving the card.'));

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfCreateProfile()
    {
        $params = [
            'form_key' => 'key',
            'key' => 'value'
        ];
        $createData = ['key' => 'value'];
        $customerId = 53445;

        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'succeeded', 'redirect' => '/'])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with($createData, $customerId);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->never())->method('addSuccessMessage');

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfFailToCreateProfileWithPublicHash()
    {
        $message = 'error';
        $exception = new LocalizedException(__($message));

        $publicHash = 'hash';
        $params = [
            PaymentTokenInterface::PUBLIC_HASH => $publicHash,
            'form_key' => 'key',
            'key' => 'value',
            'key2' => 'value2'
        ];
        $createData = ['key' => 'value', 'key2' => 'value2'];
        $customerId = 53445;

        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'failed'])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with([PaymentTokenInterface::PUBLIC_HASH => $publicHash] + $createData, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfFailToCreateProfileWithPublicHashAndGeneralException()
    {
        $exception = new \InvalidArgumentException('error message');

        $publicHash = 'hash';
        $params = [
            PaymentTokenInterface::PUBLIC_HASH => $publicHash,
            'form_key' => 'key',
            'key' => 'value'
        ];
        $createData = ['key' => 'value'];
        $customerId = 53445;

        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'failed'])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with([PaymentTokenInterface::PUBLIC_HASH => $publicHash] + $createData, $customerId)
            ->willThrowException($exception);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, __('An error occurred while saving the card.'));

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    public function testExecuteIfCreateProfileWithPublicHash()
    {
        $publicHash = 'hash';
        $params = [
            PaymentTokenInterface::PUBLIC_HASH => $publicHash,
            'form_key' => 'key',
            'key' => 'value'
        ];
        $createData = ['key' => 'value'];
        $customerId = 53445;

        $resultMock = $this->createResultJsonMock();
        $resultMock->expects($this->once())
            ->method('setData')
            ->with(['state' => 'succeeded', 'redirect' => '/'])
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->formKeyValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->requestMock)
            ->willReturn(true);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->vaultFormMock->expects($this->once())
            ->method('createProfile')
            ->with([PaymentTokenInterface::PUBLIC_HASH => $publicHash] + $createData, $customerId);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->messageManagerMock->expects($this->never())->method('addSuccessMessage');

        $this->assertSame($resultMock, $this->saveController->execute());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\Result\Json
     */
    private function createResultJsonMock()
    {
        return $this->getMockBuilder(ResultJson::class)->disableOriginalConstructor()->getMock();
    }
}
