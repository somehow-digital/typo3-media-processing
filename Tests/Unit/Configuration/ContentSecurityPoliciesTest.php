<?php
declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderFactory;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ContentSecurityPoliciesTest extends UnitTestCase
{
	#[Test]
	#[DataProvider('endpointProvider')]
	public function contentSecurityPoliciesOnlyAddsAbsoluteEndpoints(?string $endpoint, bool $shouldContainMutation): void
	{
		$providerMock = $this->createMock(ProviderInterface::class);
		$providerMock->method('hasConfiguration')->willReturn(true);
		$providerMock->method('getEndpoint')->willReturn($endpoint);

		$providerFactoryMock = $this->createMock(ProviderFactory::class);
		$providerFactoryMock->method('__invoke')->willReturn($providerMock);


		GeneralUtility::addInstance(ProviderFactory::class, $providerFactoryMock);

		$cspMap = require __DIR__ . '/../../../Configuration/ContentSecurityPolicies.php'; // NOSONAR php:S4833 - config file returns a value, no class to import

		$frontendCollection = $cspMap->offsetGet(Scope::frontend());

		$hasImgSrcMutation = false;
		foreach ($frontendCollection->mutations as $mutation) {
			if ($mutation->directive === Directive::ImgSrc) {
				$hasImgSrcMutation = true;
				break;
			}
		}

		self::assertSame($shouldContainMutation, $hasImgSrcMutation);
	}

	public static function endpointProvider(): \Generator
	{
		yield 'relative path is ignored' => ['/_assets/processed', false];
		yield 'relative path without slash is ignored' => ['assets/processed', false];
		yield 'http URL is included' => ['http://cdn.example.com/assets', true];
		yield 'https URL is included' => ['https://cdn.example.com/assets', true];
	}

	protected function tearDown(): void
	{
		GeneralUtility::purgeInstances();
		parent::tearDown();
	}
}
