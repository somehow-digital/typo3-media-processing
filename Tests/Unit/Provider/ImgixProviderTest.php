<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgixBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\ImgixProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgixProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://my-source.imgix.net',
			'source_loader' => 'folder',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'imgix-secure-token-key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('imgix', ImgixProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new ImgixProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://my-source.imgix.net', $provider->getEndpoint());
		$this->assertSame('imgix-secure-token-key', $provider->getSignatureKey());
	}

	#[Test]
	public function getSignatureKeyReturnsNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$provider = new ImgixProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-imgix-url';
		$provider = new ImgixProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new ImgixProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new ImgixProvider($this->sourceStub, $this->defaultOptions);

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
	public function configureBuildsExpectedImgixBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new ImgixProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-imgix-source-path');

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

		/** @var ImgixBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(ImgixBuilder::class, $builder);

		// Reflection-less property check via Closure binding scope
		$this->assertSame('resolved-imgix-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['fit'], (fn() => $this->fit)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['rect'] ?? null, (fn() => $this->rect)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to scale' => [
			['width' => '300', 'height' => '200'],
			[
				'fit' => 'scale',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'Crop fit mode matching width suffix c' => [
			['width' => '400c', 'height' => '250'],
			[
				'fit' => 'crop',
				'width' => 400,
				'height' => 250,
			],
		];

		yield 'Clip fit mode matching height suffix m' => [
			['height' => '500m'],
			[
				'fit' => 'clip',
				'height' => 500,
			],
		];

		yield 'Clip fit mode via explicit max dimensions' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			[
				'fit' => 'clip',
				'width' => 600,
				'height' => 400,
			],
		];

		yield 'Crop area boundaries mapped into builder rectangle array parameters' => [
			[
				'crop' => new Area(10, 20, 350, 180),
			],
			[
				'fit' => 'scale',
				'rect' => [
					10,
					20,
					350,
					180,
				],
			],
		];
	}
}
