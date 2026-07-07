<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SomehowDigital\Typo3\MediaProcessing\Provider\BunnyProvider;
use SomehowDigital\Typo3\MediaProcessing\Provider\ImgProxyProvider;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProviderFactoryTest extends UnitTestCase
{
	private ExtensionConfiguration&MockObject $extensionConfigurationMock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
	}

	#[Test]
	public function invokeReturnsNullOnUnknownProvider(): void
	{
		$this->extensionConfigurationMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn([
				'common' => ['provider' => 'unknown_provider'],
				'provider' => [],
			]);

		$factory = new ProviderFactory($this->extensionConfigurationMock);

		$this->assertNull($factory());
	}

	#[Test]
	#[DataProvider('providerConfigDataProvider')]
	public function invokeReturnsCorrectProviderInstance(array $mockConfig, string $expectedInstanceOf): void
	{
		$this->extensionConfigurationMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn($mockConfig);

		$factory = new ProviderFactory($this->extensionConfigurationMock);
		$result = $factory();

		$this->assertInstanceOf($expectedInstanceOf, $result);
	}

	/**
	 * Data provider using positional arrays within the generator.
	 */
	public static function providerConfigDataProvider(): \Generator
	{
		yield 'Bunny Provider' => [
			[
				'common' => ['provider' => 'bunny'],
				'provider' => [
					'bunny' => [],
				],
			],
			BunnyProvider::class,
		];

		yield 'ImgProxy Provider with Uri Source' => [
			[
				'common' => ['provider' => 'imgproxy'],
				'provider' => [
					'imgproxy' => [
						'source_loader' => 'uri',
						'source_uri' => 'https://example.com',
					],
				],
			],
			ImgProxyProvider::class,
		];

		yield 'ImgProxy Provider with File Source' => [
			[
				'common' => ['provider' => 'imgproxy'],
				'provider' => [
					'imgproxy' => [
						'source_loader' => 'file',
					],
				],
			],
			ImgProxyProvider::class,
		];
	}
}
