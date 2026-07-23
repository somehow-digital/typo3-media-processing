<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Builder\ThumborBuilder;
use SomehowDigital\Typo3\MediaProcessing\Provider\ThumborProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ThumborProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://thumbor.example.com',
			'source_loader' => 'uri',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'thumbor_secret_key',
			'signature_algorithm' => 'sha256',
			'signature_length' => 40,
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('thumbor', ThumborProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new ThumborProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://thumbor.example.com', $provider->getEndpoint());
		$this->assertSame('thumbor_secret_key', $provider->getSignatureKey());
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
		$provider = new ThumborProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
		$this->assertNull($provider->getSignatureAlgorithm());
		$this->assertNull($provider->getSignatureLength());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-thumbor-endpoint';
		$provider = new ThumborProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new ThumborProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new ThumborProvider($this->sourceStub, $this->defaultOptions);

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
	public function configureBuildsExpectedThumborBuilder(array $processingConfig, string $expectedType, array $expectedDimensions): void
	{
		$provider = new ThumborProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-thumbor-source-path');

		$targetFileStub = $this->getMockBuilder(ProcessedFile::class)
			->disableOriginalConstructor()
			->onlyMethods(['getProcessingConfiguration', 'getOriginalFile', 'updateProperties', 'setName'])
			->getMock();
		$targetFileStub
			->method('getProcessingConfiguration')
			->willReturn($processingConfig);

		$taskStub = $this->getMockBuilder(TaskInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$taskStub
			->method('getSourceFile')
			->willReturn($fileStub);

		$taskStub
			->method('getTargetFile')
			->willReturn($targetFileStub);

		// Required setup for internal execution of ImageDimension::fromProcessingTask($task)

		/** @var ThumborBuilder $builder */
		$builder = $provider->configure($taskStub);

		$this->assertInstanceOf(ThumborBuilder::class, $builder);

		// Reflection-less property check via Closure binding scope
		$this->assertSame('resolved-thumbor-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedType, (fn() => $this->type)->call($builder), 'Type mapping failed');
		$this->assertSame($expectedDimensions['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedDimensions['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedDimensions['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Stretch type mapping default fallback' => [
			['width' => '300', 'height' => '200'],
			'stretch',
			['width' => 300, 'height' => 200],
		];

		yield 'Fit-in type mapping via width suffix m' => [
			['width' => '400m'],
			'fit-in',
			['width' => 400],
		];

		yield 'Fit-in type mapping via height suffix m' => [
			['height' => '500m'],
			'fit-in',
			['height' => 500],
		];

		yield 'Fit-in type mapping via width suffix c' => [
			['width' => '400c'],
			'fit-in',
			['width' => 400],
		];

		yield 'Fit-in type mapping via height suffix c' => [
			['height' => '500c'],
			'fit-in',
			['height' => 500],
		];

		yield 'Fit-in type mapping via explicit max dimensions' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			'fit-in',
			['width' => 600, 'height' => 400],
		];

		yield 'Crop dimension positional mapping assignments' => [
			[
				'crop' => new Area(15, 30, 450, 250),
			],
			'stretch',
			[
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
