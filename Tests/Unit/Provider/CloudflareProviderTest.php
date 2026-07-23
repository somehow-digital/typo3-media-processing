<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudflareBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\CloudflareProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudflareProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://images.example.com',
			'source_uri' => 'https://origin.example.com',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('cloudflare', CloudflareProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new CloudflareProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://images.example.com', $provider->getEndpoint());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'not-a-valid-url';
		$provider = new CloudflareProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new CloudflareProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new CloudflareProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'application/pdf', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedCloudflareBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new CloudflareProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getProperty')
			->willReturnMap([
				['width', 1000],
				['height', 800],
			]);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-cloudflare-source-path');

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

		/** @var CloudflareBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(CloudflareBuilder::class, $builder);

		// Reflection-less closure extraction to verify internal properties
		$this->assertSame('resolved-cloudflare-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['fit'] ?? 'contain', (fn() => $this->fit)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['trim'] ?? null, (fn() => $this->trim)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default fit contain mapping with standard dimensions' => [
			['width' => '300', 'height' => '200'],
			[
				'fit' => 'contain',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'Cover fit switch via width suffix c' => [
			['width' => '400c', 'maxHeight' => 300],
			[
				'fit' => 'cover',
				'width' => 400,
				'height' => 300,
			],
		];

		yield 'Crop trimming calculation relative to 1000x800 base boundaries' => [
			[
				// Area: left=50, top=60, width=400, height=300
				'crop' => new Area(50, 60, 400, 300),
			],
			[
				'fit' => 'contain',
				'trim' => [
					60,
					550,
					440,
					50,
				],
			],
		];
	}
}
