<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Command\VaultPurchaseCommand;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultPurchaseCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\VaultPurchaseCommand
     */
    protected $vaultPurchaseCommand;

    protected function setUp(): void
    {
        $this->initProperties();
        $this->vaultPurchaseCommand = new VaultPurchaseCommand(
            $this->requestBuilderMock,
            $this->platformMock,
            $this->storeManagerMock,
            $this->subjectReaderMock,
            $this->handlerMock,
            $this->validatorMock,
            $this->platformPaymentProfileServiceMock,
            $this->platformTransactionServiceMock,
            $this->loggerMock
        );
    }

    /**
     * @dataProvider executeIfFailToProcessTransactionDataProvider
     * @param array $requestData
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('executeIfFailToProcessTransactionDataProvider')]
    public function testExecuteIfFailToProcessTransaction(array $requestData)
    {
        $this->expectException(\Magento\Payment\Gateway\Command\CommandException::class);
        $this->expectExceptionMessage('Transaction has been declined. Please try again later.');
        $exception = new \InvalidArgumentException('Payment profile was not passed');
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->processTransactionFail($requestData, $exception);
        $this->vaultPurchaseCommand->execute($this->commandSubject);
    }

    /**
     * @return array
     */
    public static function executeIfFailToProcessTransactionDataProvider()
    {
        return [
            'Payment profile id is not set' => [
                'requestData' => []
            ],
            'Payment profile id is empty' => [
                'requestData' => [VaultDataBuilder::PAYMENT_PROFILE_ID => '']
            ],
        ];
    }

    public function testExecute()
    {
        $profileId = 123;
        $requestData = [VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId];
        $transactionMock = $this->createTransactionMock();
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('purchaseByProfile')
            ->with($requestData, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->vaultPurchaseCommand->execute($this->commandSubject);
    }
}
