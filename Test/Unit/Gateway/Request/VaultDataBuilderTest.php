<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\VaultDataBuilder
     */
    protected $vaultDataBuilder;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->vaultDataBuilder = new VaultDataBuilder($this->subjectReaderMock);
    }

    /**
       * @dataProvider failToBuildWithEmptyVaultDataProvider
     * @param bool $withExtensionAttributes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('failToBuildWithEmptyVaultDataProvider')]
    public function testFailToBuildWithEmptyVault($withExtensionAttributes)
    {
        $subject = ['subject'];
        $extensionAttributes = $withExtensionAttributes
            ? $this->createMock(OrderPaymentExtensionInterface::class)
            : null;
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The vault is not found.');

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->vaultDataBuilder->build($subject);
    }

    /**
     * @return array
     */
    public static function failToBuildWithEmptyVaultDataProvider()
    {
        return [
            'Without extension attributes' => [false],
            'With extension attributes' => [true],
        ];
    }

    public function testBuild()
    {
        $profileId = 'token';
        $uniqueId = 'unique_id';
        $orderToken = 'orderToken1234';
        $subject = ['subject'];
        $result = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId,
            TransactionInterface::UNIQUE_ID => $uniqueId,
            TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN => $orderToken
        ];

        $paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
        $paymentTokenMock->expects($this->once())
            ->method('getGatewayToken')
            ->willReturn($profileId);

        $extensionAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->getMock();
        $extensionAttributes
            ->method('getVaultPaymentToken')
            ->willReturn($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $paymentInfoMock->expects($this->exactly(4))
            ->method('getAdditionalInformation')
            ->willReturnMap([
                [TransactionInterface::UNIQUE_ID, $uniqueId],
                [TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN, $orderToken]
            ]);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->vaultDataBuilder->build($subject));
    }

    public function testBuildWithoutUniqueId()
    {
        $profileId = 'token';
        $orderToken = 'orderToken4567';
        $subject = ['subject'];
        $result = [
            VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId,
            TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN => $orderToken
        ];

        $paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
        $paymentTokenMock->expects($this->once())
            ->method('getGatewayToken')
            ->willReturn($profileId);

        $extensionAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->getMock();
        $extensionAttributes
            ->method('getVaultPaymentToken')
            ->willReturn($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $paymentInfoMock->expects($this->exactly(4))
            ->method('getAdditionalInformation')
            ->willReturnMap([
                [TransactionInterface::UNIQUE_ID, false],
                [TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN, $orderToken]
            ]);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->vaultDataBuilder->build($subject));
    }
}
