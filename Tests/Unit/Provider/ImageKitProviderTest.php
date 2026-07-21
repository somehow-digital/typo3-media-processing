<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImageKitBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\ImageKitProvider;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImageKitProviderTest extends UnitTestCase
{
	protected SourceInterface&Stub $sourceStub;
	protected array $defaultOptions;

	protected function setUp(): void
	{
		parent::setUp();
		$this->sourceStub = $this->createStub(SourceInterface::class);
		$this->defaultOptions = [
			'api_endpoint' => 'https://ik.imagekit.io/mycompany',
			'source_uri' => 'https://origin.example.com',
			'signature' => true,
			'signature_key' => 'imagekit-private-key',
		];
	}

	#[Test]
	public function getIdentifierReturnsCorrectString(): void
	{
		$this->assertSame('imagekit', ImageKitProvider::getIdentifier());
	}

	#[Test]
	public function gettersReturnConfiguredValues(): void
	{
		$provider = new ImageKitProvider($this->sourceStub, $this->defaultOptions);

		$this->assertSame('https://ik.imagekit.io/mycompany', $provider->getEndpoint());
		$this->assertSame('imagekit-private-key', $provider->getSignatureKey());
	}

	#[Test]
	public function getSignatureKeyReturnsNullWhenEmpty(): void
	{
		$options = $this->defaultOptions;
		$options['signature_key'] = '';
		$provider = new ImageKitProvider($this->sourceStub, $options);

		$this->assertNull($provider->getSignatureKey());
	}

	#[Test]
	public function hasConfigurationReturnsFalseForInvalidUrl(): void
	{
		$options = $this->defaultOptions;
		$options['api_endpoint'] = 'invalid-endpoint';
		$provider = new ImageKitProvider($this->sourceStub, $options);

		$this->assertFalse($provider->hasConfiguration());
	}

	#[Test]
	public function hasConfigurationReturnsTrueForValidUrl(): void
	{
		$provider = new ImageKitProvider($this->sourceStub, $this->defaultOptions);
		$this->assertTrue($provider->hasConfiguration());
	}

	#[Test]
	#[DataProvider('supportsDataProvider')]
	public function supportsValidatesTaskCorrectly(string $taskName, string $mimeType, bool $expected): void
	{
		$provider = new ImageKitProvider($this->sourceStub, $this->defaultOptions);

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
		yield 'Supported svg format' => ['Preview', 'image/svg', true];
		yield 'Supported heic format' => ['Preview', 'image/heic', true];
		yield 'Supported video helper youtube' => ['Preview', 'video/youtube', true];
		yield 'Supported video helper vimeo' => ['Preview', 'video/vimeo', true];
		yield 'Unsupported task name' => ['CustomTask', 'image/jpeg', false];
		yield 'Unsupported mime type' => ['Preview', 'application/pdf', false];
	}

	#[Test]
	#[DataProvider('configurationDataProvider')]
	public function configureBuildsExpectedImageKitBuilder(array $processingConfig, array $expectedProperties): void
	{
		$provider = new ImageKitProvider($this->sourceStub, $this->defaultOptions);

		$fileStub = $this->createStub(File::class);

		$this->sourceStub
			->method('getSource')
			->willReturn('resolved-imagekit-source-path');

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

		/** @var ImageKitBuilder $builder */
		$builder = $provider->configure($taskMock);

		$this->assertInstanceOf(ImageKitBuilder::class, $builder);

		// Reflection-less context property validation via Closure binding scope
		$this->assertSame('resolved-imagekit-source-path', (fn() => $this->source)->call($builder));
		$this->assertSame($expectedProperties['mode'], (fn() => $this->mode)->call($builder));
		$this->assertSame($expectedProperties['width'] ?? null, (fn() => $this->width)->call($builder));
		$this->assertSame($expectedProperties['height'] ?? null, (fn() => $this->height)->call($builder));
		$this->assertSame($expectedProperties['crop'] ?? null, (fn() => $this->crop)->call($builder));
	}

	public static function configurationDataProvider(): \Generator
	{
		yield 'Default switch fallback maps to force' => [
			['width' => '300', 'height' => '200'],
			[
				'mode' => 'force',
				'width' => 300,
				'height' => 200,
			],
		];

		yield 'At_max mode via explicit max dimensions' => [
			['maxWidth' => 600, 'maxHeight' => 400],
			[
				'mode' => 'at_max',
				'width' => 600,
				'height' => 400,
			],
		];

		yield 'At_max mode matching width suffix m' => [
			['width' => '500m'],
			[
				'mode' => 'at_max',
				'width' => 500,
			],
		];

		yield 'Maintain_ratio mode matching height suffix c' => [
			['height' => '450c'],
			[
				'mode' => 'maintain_ratio',
				'height' => 450,
			],
		];

		yield 'Crop area boundaries structural assignments' => [
			[
				'crop' => new Area(10, 20, 350, 180),
			],
			[
				'mode' => 'force',
				'crop' => [
					350,
					180,
					10,
					20,
				],
			],
		];
	}
}
