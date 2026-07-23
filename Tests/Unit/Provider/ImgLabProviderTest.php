<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgLabBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\ImgLabProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgLabProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://assets.imglab-cdn.net',
			'source_loader' => 'web',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'imglab-secret-key',
			'signature_salt' => 'imglab-secret-salt',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('imglab', ImgLabProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new ImgLabProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://assets.imglab-cdn.net', $provider->getEndpoint());
		$this->assertTrue($provider->hasSignature());
		$this->assertSame('imglab-secret-key', $provider->getSignatureKey());
		$this->assertSame('imglab-secret-salt', $provider->getSignatureSalt());
	}

	#[Test]
	public function signatureGettersReturnNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$options['signature_salt'] = '';
		$provider = new ImgLabProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
		$this->assertNull($provider->getSignatureSalt());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-imglab-url';
		$provider = new ImgLabProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new ImgLabProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new ImgLabProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported jp2 format' => ['Preview', 'image/jp2', true];
		yield 'Supported jpx format' => ['Preview', 'image/jpx', true];
		yield 'Supported jpm format' => ['Preview', 'image/jpm', true];
		yield 'Supported gif format' => ['Preview', 'image/gif', true];
		yield 'Supported webp format' => ['Preview', 'image/webp', true];
		yield 'Supported heic format' => ['Preview', 'image/heic', true];
		yield 'Supported pdf format' => ['Preview', 'application/pdf', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'image/tiff', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedImgLabBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new ImgLabProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-imglab-source-path');

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

		/** @var ImgLabBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(ImgLabBuilder::class, $builder);

		// Reflection-less property verification via Closure binding scope
		$this->assertSame('resolved-imglab-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['mode'], (fn() => $this->mode)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['region'] ?? null, (fn() => $this->region)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to clip' => [
			['width' => '300', 'height' => '200'],
			[
				'mode' => 'clip',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'Crop mode matching width suffix c' => [
			['width' => '400c', 'height' => '250'],
			[
				'mode' => 'crop',
				'width' => 400,
				'height' => 250,
			],
		];

		yield 'Clip mode via explicit max dimensions fallback' => [
			['maxWidth' => 500, 'maxHeight' => 350],
			[
				'mode' => 'clip',
				'width' => 500,
				'height' => 350,
			],
		];

		yield 'Crop area boundaries mapped into builder region properties' => [
			[
				'crop' => new Area(15, 25, 450, 220),
			],
			[
				'mode' => 'clip',
				'region' => [
					15,
					25,
					450,
					220,
				],
			],
		];
	}
}
