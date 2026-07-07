<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\SirvBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\SirvProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SirvProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://myaccount.sirv.com',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('sirv', SirvProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new SirvProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://myaccount.sirv.com', $provider->getEndpoint());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-sirv-url';
		$provider = new SirvProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new SirvProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new SirvProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported gif format' => ['Preview', 'image/gif', true];
		yield 'Supported heic format' => ['Preview', 'image/heic', true];
		yield 'Supported bmp format' => ['Preview', 'image/bmp', true];
		yield 'Supported eps format' => ['Preview', 'image/eps', true];
		yield 'Supported tiff format' => ['Preview', 'image/tiff', true];
		yield 'Supported pdf format' => ['Preview', 'application/pdf', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'image/x-unsupported', false];
	}
	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedSirvBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new SirvProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getProperty')
			->willReturnCallback(static function (string $propertyName) {
				return match ($propertyName) {
					'width' => 1000,
					'height' => 800,
					default => null,
				};
			});

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-sirv-source-path');

		$targetFileStub = $this->createStub(ProcessedFile::class);
		$targetFileStub
			->method('getProcessingConfiguration')
			->willReturn($processingConfig);

		$targetFileStub
			->method('getOriginalFile')
			->willReturn($fileStub);

		$taskStub = $this->createStub(TaskInterface::class);
		$taskStub
			->method('getSourceFile')
			->willReturn($fileStub);

		$taskStub
			->method('getTargetFile')
			->willReturn($targetFileStub);

		// Required setup for internal execution of ImageDimension::fromProcessingTask($task)
		$targetFileStub->method('getTask')->willReturn($taskStub);

		/** @var SirvBuilder $builder */
		$builder = $provider->configure($taskStub);

		$this->assertInstanceOf(SirvBuilder::class, $builder);

		// Reflection-less property check via Closure binding scope
		$this->assertSame('resolved-sirv-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['scale'], (fn() => $this->scale)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}
	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to ignore' => [
			['width' => '300', 'height' => '200'],
			[
				'scale' => 'ignore',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'Fill scale mode matching width suffix c' => [
			['width' => '400c', 'height' => '250'],
			[
				'scale' => 'fill',
				'width' => 400,
				'height' => 250,
			],
		];

		yield 'Fit scale mode matching height suffix m' => [
			['height' => '500m'],
			[
				'scale' => 'fit',
				'width' => 0,
				'height' => 500,
			],
		];

		yield 'Fit scale mode via explicit max dimensions' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			[
				'scale' => 'fit',
				'width' => 600,
				'height' => 400,
			],
		];

		yield 'Crop area dynamic boundaries scaling math relative to 2000x1600 base layout' => [
			[
				'crop' => new Area(100, 50, 500, 400),
				'width' => 500,
				'height' => 400,
			],
			[
				'scale' => 'ignore',
				'width' => 2000,
				'height' => 1600,
				'crop' => [
					1000,
					800,
					200,
					100,
				],
			],
		];
	}
}
