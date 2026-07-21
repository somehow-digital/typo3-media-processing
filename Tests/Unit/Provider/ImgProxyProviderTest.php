<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgProxyBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\ImgProxyProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgProxyProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://imgproxy.example.com',
			'signature_key' => 'secret_key',
			'signature_salt' => 'secret_salt',
			'signature_size' => 32,
			'encryption_key' => 'encrypt_key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('imgproxy', ImgProxyProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new ImgProxyProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://imgproxy.example.com', $provider->getEndpoint());
		$this->assertSame('secret_key', $provider->getSignatureKey());
		$this->assertSame('secret_salt', $provider->getSignatureSalt());
		$this->assertSame(32, $provider->getSignatureSize());
		$this->assertSame('encrypt_key', $provider->getEncryptionKey());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-url';
		$provider = new ImgProxyProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new ImgProxyProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $processingPdf, bool $expected): void
	{
		$options = $this->defaultOptions;
		$options['processing_pdf'] = $processingPdf;
		$provider = new ImgProxyProvider($this->sourceStub, $options);

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
		yield 'Supported task and image mime type' => ['Preview', 'image/jpeg', false, true];
		yield 'Supported CropScaleMask task' => ['CropScaleMask', 'image/png', false, true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false, false];
		yield 'Unsupported mime type' => ['Preview', 'image/tiff-unsupported', false, false];
		yield 'PDF supported when processing_pdf is true' => ['Preview', 'application/pdf', true, true];
		yield 'PDF unsupported when processing_pdf is false' => ['Preview', 'application/pdf', false, false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedImgProxyBuilder(array $processingConfig, string $expectedType, array $expectedDimensions): void
	{
		$provider = new ImgProxyProvider($this->sourceStub, $this->defaultOptions);

		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getSha1')
			->willReturn('mocked-sha1-hash');

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-source-path');

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

		/** @var ImgProxyBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(ImgProxyBuilder::class, $builder);

		// Reflection-less property check via Closure binding
		$this->assertSame($expectedType, (fn() => $this->type)->call($builder), 'Type mapping failed');
		$this->assertSame($expectedDimensions['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedDimensions['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedDimensions['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Force type mapping default' => [
			['width' => '100', 'height' => '200'],
			'force',
			['width' => 100, 'height' => 200],
		];
		yield 'Fit type mapping via max dimensions' => [
			['maxWidth' => 400, 'maxHeight' => 300],
			'fit',
			['width' => 400, 'height' => 300],
		];
		yield 'Fit type mapping via suffix' => [
			['width' => '400m'],
			'fit',
			['width' => 400],
		];
		yield 'Fill type mapping via suffix' => [
			['height' => '500c'],
			'fill',
			['height' => 500],
		];
		yield 'Crop dimension mapping via coordinates' => [
			[
				'crop' => new Area(10, 20, 100, 200),
			],
			'force',
			[
				'crop' => [
					100,
					200,
					'nowe',
					10,
					20,
				],
			],
		];
	}
}
