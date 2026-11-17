<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Response\PaymentDetailsHandler;

class PaymentDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\PaymentDetailsHandler
     */
    protected $paymentDetailsHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->paymentDetailsHandler = new PaymentDetailsHandler($this->subjectReaderMock);
    }

    public function testHandle()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];

        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->method('getId')->willReturn(1231);
        $transactionMock->method('getAvsCode')->willReturn('active');
        $transactionMock->method('getCvvCode')->willReturn(123);
        $transactionMock->expects($this->once())->method('getType')->willReturn('Capture');
        $transactionMock->expects($this->once())->method('getGatewayType')->willReturn('braintree');
        $transactionMock->expects($this->once())->method('getAvsMessage')->willReturn('avs message');
        $transactionMock->expects($this->once())->method('getCvvMessage')->willReturn('cvv message');
        $transactionMock->expects($this->once())->method('getResponseMessage')->willReturn('response message');

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('setCcTransId')->with(1231);
        $paymentInfoMock->expects($this->once())->method('setLastTransId')->with(1231);
        $paymentInfoMock->expects($this->once())->method('setCcAvsStatus')->with('active');
        $paymentInfoMock->expects($this->once())->method('setCcCidStatus')->with(123);
        $additionalInformation = [];
        $paymentInfoMock->expects($this->exactly(10))
            ->method('setAdditionalInformation')
            ->willReturnCallback(function ($key, $value) use (&$additionalInformation, $paymentInfoMock) {
                $additionalInformation[] = [$key, $value];
                return $paymentInfoMock;
            });

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($handlingSubject)
            ->willReturn($paymentDOMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readTransaction')
            ->with($response)
            ->willReturn($transactionMock);

        $this->paymentDetailsHandler->handle($handlingSubject, $response);

        $this->assertSame([
            ['transaction_id', 1231],
            ['transaction_type', 'Capture'],
            [TransactionInterface::GATEWAY_TYPE, 'braintree'],
            [TransactionInterface::AVS_CODE, 'active'],
            [TransactionInterface::AVS_MESSAGE, 'avs message'],
            [TransactionInterface::CVV_CODE, 123],
            [TransactionInterface::CVV_MESSAGE, 'cvv message'],
            [TransactionInterface::RESPONSE_MESSAGE, 'response message'],
            [TransactionInterface::STATE, null],
            [TransactionInterface::TOKEN, null],
        ], $additionalInformation);
    }
}
