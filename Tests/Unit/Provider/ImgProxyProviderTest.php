<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
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
			'signature_key' => '736563726574',
			'signature_salt' => '73616c74',
			'signature_size' => 32,
			'encryption_key' => '000102030405060708090a0b0c0d0e0f',
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
		$this->assertSame('736563726574', $provider->getSignatureKey());
		$this->assertSame('73616c74', $provider->getSignatureSalt());
		$this->assertSame(32, $provider->getSignatureSize());
		$this->assertSame('000102030405060708090a0b0c0d0e0f', $provider->getEncryptionKey());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForEmptyUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = '';
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

	public static function supportsDataProvider(): Generator
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
	public function configureBuildsExpectedUrl(array $processingConfig, string $expectedOptions): void
	{
		$provider = new ImgProxyProvider($this->sourceStub, [
			'api_endpoint' => 'https://imgproxy.example.com',
		]);

		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getSha1')
			->willReturn('mocked-sha1-hash');

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-source-path');

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
			->willReturn($fileMock);

		$taskMock
			->expects($this->once())
			->method('getTargetFile')
			->willReturn($targetFileMock);

		// Required setup for internal execution of ImageDimension::fromProcessingTask($task)

		$url = $provider->configure($taskMock)->build();

		$this->assertStringStartsWith('https://imgproxy.example.com/_/' . $expectedOptions . '/cb:mocked-sha1-hash/plain/', $url);
	}

	#[Test]
	public function configureEncryptsSourceWhenEncryptionKeyIsSet(): void
	{
		$url = $this->configureUrl([
			'encryption_key' => '000102030405060708090a0b0c0d0e0f',
		]);

		$this->assertStringContainsString('/enc/', $url);
		$this->assertStringNotContainsString('resolved-source-path', $url);
	}

	#[Test]
	public function configureTruncatesSignatureToConfiguredSize(): void
	{
		$url = $this->configureUrl([
			'signature_key' => '736563726574',
			'signature_salt' => '73616c74',
			'signature_size' => 8,
		]);

		// 8 bytes encode to 11 base64url characters.
		$this->assertMatchesRegularExpression('#^https://imgproxy\.example\.com/[\w-]{11}/#', $url);
	}

	private function configureUrl(array $options): string
	{
		$provider = new ImgProxyProvider($this->sourceStub, [
			'api_endpoint' => 'https://imgproxy.example.com',
		] + $options);

		$this->sourceStub->method('getSource')->willReturn('resolved-source-path');

		$targetFileStub = $this->createStub(ProcessedFile::class);
		$targetFileStub->method('getProcessingConfiguration')->willReturn([]);

		$taskStub = $this->createStub(TaskInterface::class);
		$taskStub->method('getSourceFile')->willReturn($this->createStub(File::class));
		$taskStub->method('getTargetFile')->willReturn($targetFileStub);

		return $provider->configure($taskStub)->build();
	}

	public static function configurationDataProvider(): Generator
	{
		yield 'Force type mapping default' => [
			[
				'width' => '100',
				'height' => '200',
			],
			'rt:force/w:100/h:200',
		];
		yield 'Fit type mapping via max dimensions' => [
			[
				'maxWidth' => 400,
				'maxHeight' => 300,
			],
			'rt:fit/w:400/h:300',
		];
		yield 'Fit type mapping via suffix' => [
			[
				'width' => '400m',
			],
			'rt:fit/w:400',
		];
		yield 'Fill type mapping via suffix' => [
			[
				'height' => '500c',
			],
			'rt:fill/h:500',
		];
		yield 'Crop dimension mapping via coordinates' => [
			[
				'crop' => new Area(10, 20, 100, 200),
			],
			'rt:force/c:100:200:nowe:10:20',
		];
	}
}
