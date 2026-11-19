<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Helper;

use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;

class SubjectReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    protected function setUp(): void
    {
        $this->subjectReader = new SubjectReader();
    }

    /**
     * @param array $subject
     * @dataProvider failToReadNotObjectTransactionDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('failToReadNotObjectTransactionDataProvider')]
    public function testFailToReadNotObjectTransaction($subject)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Response object does not exist');
        $this->subjectReader->readTransaction($subject);
    }

    /**
     * @return array
     */
    public static function failToReadNotObjectTransactionDataProvider()
    {
        return [
            'Transaction is not set' => [
                'subject' => [],
            ],
            'Transaction is not an object' => [
                'subject' => ['transaction' => 'string'],
            ],
        ];
    }

    public function testFailToReadNotTransactionInterfaceInstance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The object is not a class \\SubscribePro\\Service\\Transaction\\TransactionInterface.');
        $subject = ['transaction' => new \stdClass()];
        $this->subjectReader->readTransaction($subject);
    }

    public function testReadTransaction()
    {
        $transactionMock = $this->createStub(TransactionInterface::class);
        $subject = ['transaction' => $transactionMock];

        $this->assertSame($transactionMock, $this->subjectReader->readTransaction($subject));
    }
}
