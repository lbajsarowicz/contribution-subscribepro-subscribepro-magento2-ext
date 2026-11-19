<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class PaymentDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder
     */
    protected $paymentDataBuilder;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->paymentDataBuilder = new PaymentDataBuilder($this->subjectReaderMock);
    }

    public function testBuild()
    {
        $subject = ['subject'];
        $result = [
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => ['info'],
            VaultConfigProvider::IS_ACTIVE_CODE => 'active',
            PaymentProfileInterface::CREDITCARD_TYPE => 'visa',
            PaymentProfileInterface::CREDITCARD_MONTH => '05',
            PaymentProfileInterface::CREDITCARD_YEAR => '2030',
            PaymentProfileInterface::CREDITCARD_LAST_DIGITS => '1111',
            PaymentProfileInterface::CREDITCARD_FIRST_DIGITS => '411111'
        ];

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->exactly(4))
            ->method('getAdditionalInformation')
            ->willReturnMap([
                [PaymentDataBuilder::PAYMENT_METHOD_TOKEN, ['info']],
                [VaultConfigProvider::IS_ACTIVE_CODE, 'active'],
                [PaymentProfileInterface::CREDITCARD_LAST_DIGITS, '1111'],
                [PaymentProfileInterface::CREDITCARD_FIRST_DIGITS, '411111']
            ]);
        $paymentInfoMock->expects($this->once())->method('getCcType')->willReturn('visa');
        $paymentInfoMock->expects($this->once())->method('getCcExpMonth')->willReturn('05');
        $paymentInfoMock->expects($this->once())->method('getCcExpYear')->willReturn('2030');

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->paymentDataBuilder->build($subject));
    }
}
