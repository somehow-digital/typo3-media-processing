<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\OptimoleBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\OptimoleProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OptimoleProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();

		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_key' => 'optimole-key-12345',
			'source_uri' => 'https://origin.example.com',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('optimole', OptimoleProvider::getIdentifier());
	}

	#[Test]
	public function gettersAndEndpointTemplateReturnConfiguredValues(): void
	{
		$provider = new OptimoleProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('optimole-key-12345', $provider->getKey());

		// Verifies strtr replacement behaves exactly as expected against the template constant
		$expectedEndpoint = strtr(OptimoleBuilder::API_ENDPOINT_TEMPLATE, ['%key%' => 'optimole-key-12345']);
		$this->assertSame($expectedEndpoint, $provider->getEndpoint());
	}

	#[Test]
	public function getKeyReturnsNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['api_key'] = '';
		$provider = new OptimoleProvider($this->sourceStub, $options);

		$this->assertNull($provider->getKey());
	}

	#[Test]
	public function hasConfigurationValidatesCorrectly(): void
	{
		$options = $this->defaultOptions;
		$options['api_key'] = '';
		$providerWithoutConfig = new OptimoleProvider($this->sourceStub, $options);
		$this->assertFalse($providerWithoutConfig->hasConfiguration());

		$providerWithConfig = new OptimoleProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($providerWithConfig->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new OptimoleProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported ico format' => ['Preview', 'image/ico', true];
		yield 'Supported heic format' => ['Preview', 'image/heic', true];
		yield 'Supported tiff format' => ['Preview', 'image/tiff', true];
		yield 'Supported pdf format' => ['Preview', 'application/pdf', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'image/x-unknown-raw', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedOptimoleBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new OptimoleProvider($this->sourceStub, $this->defaultOptions);

		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getSha1')
			->willReturn('mocked-file-sha1');

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-optimole-source');

		$targetFileMock = $this->createMock(ProcessedFile::class);
		$targetFileMock
			->expects($this->once())
			->method('getProcessingConfiguration')
			->willReturn($processingConfig);

		$taskMock = $this->createMock(TaskInterface::class);
		$taskMock
			->expects($this->once())
			->method('getSourceFile')
			->willReturn($fileMock);

		$taskMock
			->expects($this->once())
			->method('getTargetFile')
			->willReturn($targetFileMock);

		/** @var OptimoleBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(OptimoleBuilder::class, $builder);

		// Context internal property validation via Closure binding scope
		$this->assertSame('resolved-optimole-source', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['type'], (fn() => $this->type)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['minWidth'] ?? null, (fn() => $this->minWidth)->call($builder));
		$this->assertSame($expectedProperties['minHeight'] ?? null, (fn() => $this->minHeight)->call($builder));
		$this->assertSame('mocked-file-sha1', (fn() => $this->hash)->call($builder));

		if (isset($expectedProperties['crop'])) {
			$this->assertSame($expectedProperties['crop'], (fn() => $this->crop)->call($builder));
		}
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to force' => [
			['width' => '300', 'height' => '200', 'minWidth' => 100, 'minHeight' => 50],
			[
				'type' => 'force',
				'width' => 300,
				'height' => 200,
				'minWidth' => 100,
				'minHeight' => 50,
			],
		];

		yield 'Fit mode matching width suffix m' => [
			['width' => '400m'],
			[
				'type' => 'fit',
				'width' => 400,
			],
		];

		yield 'Fit mode via explicit max dimensions fallback' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			[
				'type' => 'fit',
				'width' => 600,
				'height' => 400,
			],
		];

		yield 'Fill mode matching height suffix c' => [
			['height' => '500c'],
			[
				'type' => 'fill',
				'height' => 500,
			],
		];

		yield 'Crop area boundaries mapped with top-left gravity payload array' => [
			[
				'crop' => new Area(10, 20, 350, 180),
			],
			[
				'type' => 'force',
				'crop' => [
					350,
					180,
					'nowe',
					10,
					20,
				],
			],
		];
	}
}
