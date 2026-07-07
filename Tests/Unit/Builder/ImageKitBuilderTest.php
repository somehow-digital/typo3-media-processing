<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImageKitBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImageKitBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://ik.imagekit.io/demo';

	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new ImageKitBuilder(self::BASE_URL, 'secret_key');

		$builder->setSource('images/photo.jpg')
			->setMode('maintain')
			->setWidth(800)
			->setHeight(600)
			->setCrop(100, 200, 10, 20);

		$this->assertSame('images/photo.jpg', $builder->getSource());
		$this->assertSame('maintain', $builder->getMode());
		$this->assertSame(800, $builder->getWidth());
		$this->assertSame(600, $builder->getHeight());
		$this->assertSame([100, 200, 10, 20], $builder->getCrop());
	}

	#[Test]
	#[DataProvider('transformationDataProvider')]
	public function buildGeneratesCorrectUrlStructureWithoutSignature(
		string $source,
		?string $mode,
		?int $width,
		?int $height,
		?array $crop,
		string $expectedPath
	): void {
		// Instantiate without a key to test pure path transformation logic
		$builder = new ImageKitBuilder(self::BASE_URL, null);
		$builder->setSource($source);

		if ($mode !== null) {
			$builder->setMode($mode);
		}
		if ($width !== null) {
			$builder->setWidth($width);
		}
		if ($height !== null) {
			$builder->setHeight($height);
		}
		if ($crop !== null) {
			$builder->setCrop(...$crop);
		}

		$expectedUrl = 'https://ik.imagekit.io/demo/' . $expectedPath;
		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildAppliesSecureSignatureWhenKeyIsProvided(): void
	{
		$builder = new ImageKitBuilder(self::BASE_URL, 'my_secret_key');
		$builder->setSource('photo.jpg')
			->setWidth(400);

		$url = $builder->build();

		// 1. Verify the base transformations are present
		$this->assertStringStartsWith('https://ik.imagekit.io/demo/tr:cm-extract:w-400/photo.jpg', $url);

		// 2. Since time() is dynamic, use regex to check parameters: ik-t (digits) and ik-s (sha1 hex string)
		$this->assertMatchesRegularExpression('/[?&]ik-t=\d+/', $url);
		$this->assertMatchesRegularExpression('/[?&]ik-s=[a-f0-9]{40}/', $url);
	}

	public static function transformationDataProvider(): \Generator
	{
		yield 'basic source path raw url encoded' => [
			'folder/sub folder/image.jpg',
			null,
			null,
			null,
			null,
			'tr:cm-extract/folder%2Fsub%20folder%2Fimage.jpg',
		];

		yield 'resize width and height with mode' => [
			'image.jpg',
			'pad',
			300,
			200,
			null,
			'tr:cm-extract:c-pad,w-300,h-200/image.jpg',
		];

		yield 'crop options mapped to extract options' => [
			'image.jpg',
			null,
			null,
			null,
			[150, 150, 20, 10], // width, height, horizontal, vertical
			'tr:cm-extract,w-150,h-150,x-20,y-10/image.jpg',
		];

		yield 'combined crop and resize properties' => [
			'image.jpg',
			'force',
			100,
			100,
			[50, 50, 0, 0],
			'tr:cm-extract,w-50,h-50:c-force,w-100,h-100/image.jpg',
		];
	}
}
