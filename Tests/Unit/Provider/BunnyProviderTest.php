<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\BunnyBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\BunnyProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BunnyProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://myzone.b-cdn.net',
			'signature' => true,
			'signature_key' => 'bunny-secure-token-key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('bunny', BunnyProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new BunnyProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://myzone.b-cdn.net', $provider->getEndpoint());
		$this->assertSame('bunny-secure-token-key', $provider->getSignatureKey());
	}

	#[Test]
	public function getSignatureKeyReturnsNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$provider = new BunnyProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-cdn-url';
		$provider = new BunnyProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new BunnyProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new BunnyProvider($this->sourceStub, $this->defaultOptions);

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
	public function configureBuildsExpectedBunnyBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new BunnyProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-bunny-source-path');

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

		/** @var BunnyBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(BunnyBuilder::class, $builder);

		// Reflection-less property check via Closure binding to match target internal property structures
		$this->assertSame('resolved-bunny-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Width and height configuration values' => [
			['width' => '150', 'height' => '250'],
			['width' => 150, 'height' => 250],
		];

		yield 'Fallback using maxWidth and maxHeight values' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			['width' => 600, 'height' => 400],
		];

		yield 'Crop area boundaries mapped directly to options integers' => [
			[
				'crop' => new Area(15, 25, 300, 150),
			],
			[
				'crop' => [
					300,
					150,
					15,
					25,
				],
			],
		];
	}
}
