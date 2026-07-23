<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudImageBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\CloudImageProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudImageProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://token.cloudimg.io',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'cloudimage-secret-key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('cloudimage', CloudImageProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new CloudImageProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://token.cloudimg.io', $provider->getEndpoint());
		$this->assertSame('cloudimage-secret-key', $provider->getSignatureKey());
		$this->assertSame($this->sourceStub, $provider->getSource());
	}

	#[Test]
	public function getSignatureKeyReturnsNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$provider = new CloudImageProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
	}

	#[Test]
	public function hasConfigurationValidatesCorrectly(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = '';
		$providerWithoutConfig = new CloudImageProvider($this->sourceStub, $options);
		$this->assertFalse($providerWithoutConfig->hasConfiguration());

		$providerWithConfig = new CloudImageProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($providerWithConfig->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new CloudImageProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported pdf format' => ['Preview', 'application/pdf', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'image/tiff', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedCloudImageBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new CloudImageProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-cloudimage-source');

		$targetFileMock = $this->getMockBuilder(ProcessedFile::class)
			->disableOriginalConstructor()
			->onlyMethods(['getProcessingConfiguration', 'getOriginalFile', 'updateProperties', 'setName'])
			->getMock();

		$targetFileMock
			->expects($this->once())
			->method('getProcessingConfiguration')
			->willReturn($processingConfig);

		$taskMock = $this->getMockBuilder(TaskInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$taskMock
			->expects($this->once())
			->method('getSourceFile')
			->willReturn($fileStub);

		$taskMock
			->expects($this->once())
			->method('getTargetFile')
			->willReturn($targetFileMock);

		// Required setup for internal execution of ImageDimension::fromProcessingTask($task)

		/** @var CloudImageBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(CloudImageBuilder::class, $builder);

		// Reflection-less property check via Closure binding
		$this->assertSame('resolved-cloudimage-source', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['function'], (fn() => $this->function)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to cover' => [
			['width' => '300', 'height' => '200'],
			[
				'function' => 'cover',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'Crop mode matching suffix c' => [
			['width' => '400c', 'height' => '250'],
			[
				'function' => 'crop',
				'width' => 400,
				'height' => 250,
			],
		];

		yield 'Bound mode matching suffix m' => [
			['width' => '500m'],
			[
				'function' => 'bound',
				'width' => 500,
			],
		];

		yield 'Bound mode via explicit max dimensions' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			[
				'function' => 'bound',
				'width' => 600,
				'height' => 400,
			],
		];

		yield 'Crop area boundaries mapped cleanly' => [
			[
				'crop' => new Area(10, 20, 300, 150),
			],
			[
				'function' => 'cover',
				'crop' => [
					10,
					20,
					300,
					150,
				],
			],
		];
	}
}
