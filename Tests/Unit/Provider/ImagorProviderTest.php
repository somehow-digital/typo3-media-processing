<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImagorBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\ImagorProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImagorProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://imagor.example.com',
			'source_loader' => 'uri',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'imagor-secret-key',
			'signature_algorithm' => 'sha256',
			'signature_length' => 40,
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('imagor', ImagorProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new ImagorProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://imagor.example.com', $provider->getEndpoint());
		$this->assertSame('imagor-secret-key', $provider->getSignatureKey());
		$this->assertSame('sha256', $provider->getSignatureAlgorithm());
		$this->assertSame(40, $provider->getSignatureLength());
	}

	#[Test]
	public function signatureGettersReturnNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$options['signature_algorithm'] = '';
		$options['signature_length'] = null;
		$provider = new ImagorProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
		$this->assertNull($provider->getSignatureAlgorithm());
		$this->assertNull($provider->getSignatureLength());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-imagor-url';
		$provider = new ImagorProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new ImagorProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new ImagorProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getMimeType')
			->willReturn($mimeType);

		$taskStub = $this->createStub(TaskInterface::class);
		$taskStub
			->method('getName')
			->willReturn($taskName);

		$taskStub
			->method('getSourceFile')
			->willReturn($fileStub);

		$this->assertSame($expected, $provider->supports($taskStub));
	}

	public static function supportsDataProvider(): \Generator
	{
		yield 'Supported task and jpeg mime type' => ['Preview', 'image/jpeg', true];
		yield 'Supported CropScaleMask task and png' => ['CropScaleMask', 'image/png', true];
		yield 'Supported webp format' => ['Preview', 'image/webp', true];
		yield 'Supported avif format' => ['Preview', 'image/avif', true];
		yield 'Supported gif format' => ['Preview', 'image/gif', true];
		yield 'Supported bmp format' => ['Preview', 'image/bmp', true];
		yield 'Supported tiff format' => ['Preview', 'image/tiff', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'application/pdf', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedImagorBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new ImagorProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-imagor-source-path');

		$targetFileMock = $this->createMock(ProcessedFile::class);
		$targetFileMock
			->expects($this->once())
			->method('getProcessingConfiguration')
			->willReturn($processingConfig);

		$taskMock = $this->createMock(TaskInterface::class);
		$taskMock
			->expects($this->once())
			->method('getSourceFile')
			->willReturn($fileStub);

		$taskMock
			->expects($this->once())
			->method('getTargetFile')
			->willReturn($targetFileMock);

		/** @var ImagorBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(ImagorBuilder::class, $builder);

		// Reflection-less property check via Closure binding scope
		$this->assertSame('resolved-imagor-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['type'], (fn() => $this->type)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to stretch' => [
			['width' => '400', 'height' => '300'],
			[
				'type' => 'stretch',
				'width' => 400,
				'height' => 300,
			],
		];

		yield 'Fit-in mode matching width suffix m' => [
			['width' => '500m'],
			[
				'type' => 'fit-in',
				'width' => 500,
			],
		];

		yield 'Fit-in mode matching height suffix c' => [
			['height' => '600c'],
			[
				'type' => 'fit-in',
				'height' => 600,
			],
		];

		yield 'Fit-in mode via explicit max dimensions' => [
			['maxWidth' => 800, 'maxHeight' => 600],
			[
				'type' => 'fit-in',
				'width' => 800,
				'height' => 600,
			],
		];

		yield 'Crop area boundaries structural mapping' => [
			[
				'crop' => new Area(15, 30, 450, 250),
			],
			[
				'type' => 'stretch',
				'crop' => [
					450,
					250,
					15,
					30,
				],
			],
		];
	}
}
