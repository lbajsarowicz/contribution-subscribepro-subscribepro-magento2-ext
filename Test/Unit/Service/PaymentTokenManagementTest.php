<?php

namespace Swarming\SubscribePro\Test\Unit\Service;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Swarming\SubscribePro\Service\PaymentTokenManagement;

class PaymentTokenManagementTest extends \PHPUnit\Framework\TestCase
{
    public function testGetVisibleAvailableTokens()
    {
        $repository = $this->createMock(PaymentTokenRepositoryInterface::class);
        $filterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()->getMock();
        $groupBuilder = $this->getMockBuilder(FilterGroupBuilder::class)->disableOriginalConstructor()->getMock();
        $criteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()->getMock();
        $dateFactory = $this->getMockBuilder(DateTimeFactory::class)->disableOriginalConstructor()->getMock();
        $criteria = $this->createStub(SearchCriteria::class);
        $result = $this->createMock(PaymentTokenSearchResultsInterface::class);
        $token = $this->createStub(PaymentTokenInterface::class);
        $filters = [];
        $groups = [];
        for ($index = 0; $index < 4; $index++) {
            $filters[] = $this->createStub(Filter::class);
            $groups[] = $this->createStub(FilterGroup::class);
        }

        $fields = [];
        $values = [];
        $filterBuilder->expects($this->exactly(4))->method('setField')
            ->willReturnCallback(function ($field) use (&$fields, $filterBuilder) {
                $fields[] = $field;
                return $filterBuilder;
            });
        $filterBuilder->expects($this->exactly(4))->method('setValue')
            ->willReturnCallback(function ($value) use (&$values, $filterBuilder) {
                $values[] = $value;
                return $filterBuilder;
            });
        $filterBuilder->expects($this->once())->method('setConditionType')->with('gt')->willReturnSelf();
        $filterBuilder->expects($this->exactly(4))->method('create')->willReturnOnConsecutiveCalls(...$filters);

        $groupFilters = [];
        $groupBuilder->expects($this->exactly(4))->method('addFilter')
            ->willReturnCallback(function ($filter) use (&$groupFilters, $groupBuilder) {
                $groupFilters[] = $filter;
                return $groupBuilder;
            });
        $groupBuilder->expects($this->exactly(4))->method('create')->willReturnOnConsecutiveCalls(...$groups);
        $criteriaBuilder->expects($this->once())->method('setFilterGroups')->with($groups)->willReturnSelf();
        $criteriaBuilder->expects($this->once())->method('create')->willReturn($criteria);
        $dateFactory->expects($this->once())->method('create')
            ->with('now', new \DateTimeZone('UTC'))->willReturn(new \DateTime('2018-01-01 12:00:00'));
        $repository->expects($this->once())->method('getList')->with($criteria)->willReturn($result);
        $result->expects($this->once())->method('getItems')->willReturn([$token]);

        $management = new PaymentTokenManagement($repository, $filterBuilder, $groupBuilder, $criteriaBuilder, $dateFactory);
        $this->assertSame([$token], $management->getSubscribeProTokensByCustomerId(1));
        $this->assertSame([
            PaymentTokenInterface::CUSTOMER_ID,
            PaymentTokenInterface::IS_VISIBLE,
            PaymentTokenInterface::IS_ACTIVE,
            PaymentTokenInterface::EXPIRES_AT,
        ], $fields);
        $this->assertSame([1, 1, 1, '2018-01-01 00:00:00'], $values);
        $this->assertSame($filters, $groupFilters);
    }
}
