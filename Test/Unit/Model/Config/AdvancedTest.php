<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swarming\SubscribePro\Model\Config\Advanced;

class AdvancedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->advancedConfig = new Advanced($this->scopeConfigMock);
    }

    public function testGetCacheLifeTime()
    {
        $websiteCode = 'code';
        $lifeTime = 500;

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/advanced/cache_lifetime', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($lifeTime);

        $this->assertEquals($lifeTime, $this->advancedConfig->getCacheLifeTime($websiteCode));
    }

    /**
     * @dataProvider getWebhookSecretKeyDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getWebhookSecretKeyDataProvider')]
    public function testGetWebhookSecretKey($websiteCode, $secret)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/advanced/webhook_secret', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($secret);

        $this->assertSame($secret, $this->advancedConfig->getWebhookSecretKey($websiteCode));
    }

    public static function getWebhookSecretKeyDataProvider()
    {
        return [
            'default website' => [null, 'default-secret'],
            'named website' => ['main_website', 'website-secret'],
            'empty secret' => ['code', '']
        ];
    }
}
