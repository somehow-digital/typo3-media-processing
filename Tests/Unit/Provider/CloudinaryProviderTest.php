<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudinaryBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\CloudinaryProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudinaryProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://api.cloudinary.com/v1_1/demo/image/fetch',
			'delivery_mode' => 'fetch',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_algorithm' => 'sha256',
			'signature_key' => 'cloudinary-secret-key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('cloudinary', CloudinaryProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new CloudinaryProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://api.cloudinary.com/v1_1/demo/image/fetch', $provider->getEndpoint());
		$this->assertSame('cloudinary-secret-key', $provider->getSignatureKey());
		$this->assertSame('sha256', $provider->getSignatureAlgorithm());
		$this->assertSame($this->sourceStub, $provider->getSource());
	}

	#[Test]
	public function signatureGettersReturnNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$options['signature_algorithm'] = '';
		$provider = new CloudinaryProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
		$this->assertNull($provider->getSignatureAlgorithm());
	}

	#[Test]
	public function hasConfigurationValidatesCorrectly(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = '';
		$providerWithoutConfig = new CloudinaryProvider($this->sourceStub, $options);
		$this->assertFalse($providerWithoutConfig->hasConfiguration());

		$providerWithConfig = new CloudinaryProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($providerWithConfig->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new CloudinaryProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported heic format' => ['Preview', 'image/heic', true];
		yield 'Supported tiff format' => ['Preview', 'image/tiff', true];
		yield 'Supported pdf format' => ['Preview', 'application/pdf', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'image/x-unsupported', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedCloudinaryBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new CloudinaryProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-cloudinary-source');

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

		/** @var CloudinaryBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(CloudinaryBuilder::class, $builder);

		// Reflection-less property check via Closure binding
		$this->assertSame('resolved-cloudinary-source', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['mode'], (fn() => $this->mode)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default fit fallback mapping maps to scale' => [
			['width' => '300', 'height' => '200'],
			[
				'mode' => 'scale',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'Fill mode matching width suffix c' => [
			['width' => '400c', 'height' => '250'],
			[
				'mode' => 'fill',
				'width' => 400,
				'height' => 250,
			],
		];

		yield 'Fit mode matching height suffix m' => [
			['height' => '500m'],
			[
				'mode' => 'fit',
				'height' => 500,
			],
		];

		yield 'Fit mode via explicit max dimensions' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			[
				'mode' => 'fit',
				'width' => 600,
				'height' => 400,
			],
		];

		yield 'Crop area boundaries mapped cleanly' => [
			[
				'crop' => new Area(12, 24, 320, 160),
			],
			[
				'mode' => 'scale',
				'crop' => [
					12,
					24,
					320,
					160,
				],
			],
		];
	}
}
