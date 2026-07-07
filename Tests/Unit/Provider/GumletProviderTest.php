<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\GumletBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\GumletProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GumletProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://demo.gumlet.io',
			'source_loader' => 'folder',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'gumlet-secure-token-key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('gumlet', GumletProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new GumletProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://demo.gumlet.io', $provider->getEndpoint());
		$this->assertTrue($provider->hasSignature());
		$this->assertSame('gumlet-secure-token-key', $provider->getSignatureKey());
	}

	#[Test]
	public function getSignatureKeyReturnsNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$provider = new GumletProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-gumlet-url';
		$provider = new GumletProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new GumletProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new GumletProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported jxl format' => ['Preview', 'image/jxl', true];
		yield 'Supported heic format' => ['Preview', 'image/heic', true];
		yield 'Supported pdf format' => ['Preview', 'application/pdf', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'image/tiff-unsupported', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedGumletBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new GumletProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-gumlet-source-path');

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

		/** @var GumletBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(GumletBuilder::class, $builder);

		// Reflection-less property verification via Closure binding scope
		$this->assertSame('resolved-gumlet-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Width and height explicit values' => [
			['width' => '220', 'height' => '330'],
			['width' => 220, 'height' => 330],
		];

		yield 'Fallback mapping utilizing maxWidth and maxHeight rules' => [
			['maxWidth' => 800, 'maxHeight' => 600],
			['width' => 800, 'height' => 600],
		];

		yield 'Crop area boundaries mapped into builder schema integers' => [
			[
				'crop' => new Area(8, 16, 400, 200),
			],
			[
				'crop' => [
					8,
					16,
					400,
					200,
				],
			],
		];
	}
}
