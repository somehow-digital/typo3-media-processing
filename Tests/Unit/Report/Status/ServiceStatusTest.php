<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Report\Status;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use SomehowDigital\Typo3\MediaProcessing\Report\Status\ServiceStatus;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Status;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ServiceStatusTest extends UnitTestCase
{
	protected ExtensionConfiguration&MockObject $extensionConfigMock;
	protected LanguageService&Stub $languageServiceStub;

	protected function setUp(): void
	{
		parent::setUp();

		$this->extensionConfigMock = $this->createMock(ExtensionConfiguration::class);
		$this->languageServiceStub = $this->createStub(LanguageService::class);

		$this->languageServiceStub->method('sL')->willReturnArgument(0);
		$GLOBALS['LANG'] = $this->languageServiceStub;
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['LANG']);
		parent::tearDown();
	}

	#[Test]
	public function getLabelReturnsCorrectLllString(): void
	{
		$this->extensionConfigMock
			->expects($this->once())
			->method('get');

		$serviceStatus = new ServiceStatus($this->createStub(ProviderInterface::class), $this->extensionConfigMock);

		$this->assertSame(
			'LLL:EXT:media_processing/Resources/Private/Language/report.xlf:label',
			$serviceStatus->getLabel()
		);
	}

	#[Test]
	public function getStatusReturnsOnlyProviderStatusWhenProviderIsNull(): void
	{
		$this->extensionConfigMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn(['common' => ['provider' => 'local']]);

		$serviceStatus = new ServiceStatus(null, $this->extensionConfigMock);
		$statusArray = $serviceStatus->getStatus();

		$this->assertArrayHasKey('provider', $statusArray);
		$this->assertArrayNotHasKey('configuration', $statusArray);
		$this->assertArrayNotHasKey('frontend', $statusArray);
		$this->assertArrayNotHasKey('backend', $statusArray);
		$this->assertArrayNotHasKey('storage', $statusArray);

		$providerStatus = $statusArray['provider'];
		$this->assertInstanceOf(Status::class, $providerStatus);
		$this->assertSame('local', $providerStatus->getValue());
	}

	#[Test]
	#[DataProvider('statusDataProvider')]
	public function getStatusReturnsExpectedReportStatuses(
		array $configData,
		bool $hasConfiguration,
		string $expectedEndpoint,
		array $expectedSeverities,
		array $expectedValues
	): void {
		$this->extensionConfigMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn($configData);

		$providerMock = $this->createMock(ProviderInterface::class);
		$providerMock
			->expects($this->once())
			->method('getEndpoint')
			->willReturn($expectedEndpoint);

		$providerMock
			->expects($this->atLeastOnce())
			->method('hasConfiguration')
			->willReturn($hasConfiguration);

		$serviceStatus = new ServiceStatus($providerMock, $this->extensionConfigMock);
		$statusArray = $serviceStatus->getStatus();

		// Assert that all keys exist when a provider is present
		$this->assertCount(5, $statusArray);

		foreach (['provider', 'configuration', 'frontend', 'backend', 'storage'] as $key) {
			$statusObj = $statusArray[$key];
			$this->assertInstanceOf(Status::class, $statusObj);
			$this->assertSame($expectedSeverities[$key], $statusObj->getSeverity(), "Severity mismatch for key: {$key}");
			$this->assertSame($expectedValues[$key], $statusObj->getValue(), "Value mismatch for key: {$key}");
		}
	}

	public static function statusDataProvider(): \Generator
	{
		yield 'All features enabled, valid remote configuration' => [
			'configData' => [
				'common' => [
					'provider' => 'imgproxy',
					'frontend' => true,
					'backend' => true,
					'storage' => true,
				],
			],
			'hasConfiguration' => true,
			'expectedEndpoint' => 'https://imgproxy.example.com',
			'expectedSeverities' => [
				'provider' => ContextualFeedbackSeverity::INFO,
				'configuration' => ContextualFeedbackSeverity::OK,
				'frontend' => ContextualFeedbackSeverity::OK,
				'backend' => ContextualFeedbackSeverity::OK,
				'storage' => ContextualFeedbackSeverity::WARNING,
			],
			'expectedValues' => [
				'provider' => 'imgproxy',
				'configuration' => 'valid',
				'frontend' => 'enabled',
				'backend' => 'enabled',
				'storage' => 'enabled',
			],
		];

		yield 'All features disabled, invalid configuration' => [
			'configData' => [
				'common' => [
					'provider' => '', // Fallback testing to 'local'
					'frontend' => false,
					'backend' => false,
					'storage' => false,
				],
			],
			'hasConfiguration' => false,
			'expectedEndpoint' => '',
			'expectedSeverities' => [
				'provider' => ContextualFeedbackSeverity::INFO,
				'configuration' => ContextualFeedbackSeverity::ERROR,
				'frontend' => ContextualFeedbackSeverity::WARNING,
				'backend' => ContextualFeedbackSeverity::WARNING,
				'storage' => ContextualFeedbackSeverity::OK,
			],
			'expectedValues' => [
				'provider' => 'local',
				'configuration' => 'invalid',
				'frontend' => 'disabled',
				'backend' => 'disabled',
				'storage' => 'disabled',
			],
		];
	}
}
