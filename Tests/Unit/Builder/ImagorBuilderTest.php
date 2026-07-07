<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImagorBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImagorBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://imagor.example.com';

	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new ImagorBuilder(self::BASE_URL, 'secret', 'sha256', 40);

		$builder->setSource('images/photo.jpg')
			->setType('fit-in')
			->setWidth(800)
			->setHeight(600)
			->setCrop(100, 200, 10, 20);

		$this->assertSame('images/photo.jpg', $builder->getSource());
		$this->assertSame('fit-in', $builder->getType());
		$this->assertSame(800, $builder->getWidth());
		$this->assertSame(600, $builder->getHeight());
		$this->assertSame([100, 200, 10, 20], $builder->getCrop());
	}

	#[Test]
	public function buildReturnsUnsafePathWhenNoKeyIsProvided(): void
	{
		$builder = new ImagorBuilder(self::BASE_URL, null, 'sha256', 40);
		$builder->setSource('my-image.jpg')
			->setWidth(300)
			->setHeight(200);

		// Imagor falls back to /unsafe/ prefix when no signing key exists
		$expectedUrl = 'https://imagor.example.com/unsafe/300x200/my-image.jpg';
		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	#[DataProvider('imagorPathDataProvider')]
	public function buildGeneratesCorrectPathStructureAndSignature(
		?string $type,
		?int $width,
		?int $height,
		?array $crop,
		string $expectedPath,
		string $expectedSignature
	): void {
		// Using fixed config strings to test deterministic signature outputs
		$builder = new ImagorBuilder(self::BASE_URL, 'test-signing-key', 'sha256', 10);
		$builder->setSource('folder/image.png');

		if ($type !== null) {
			$builder->setType($type);
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

		$expectedUrl = sprintf('https://imagor.example.com/%s/%s', $expectedSignature, $expectedPath);
		$this->assertSame($expectedUrl, $builder->build());
	}

	public static function imagorPathDataProvider(): \Generator
	{
		yield 'only resizing coordinates' => [
			null,
			400,
			300,
			null,
			'400x300/folder%2Fimage.png',
			'2rUP_K50ul', // Pre-calculated URL-safe base64 hmac snippet
		];

		yield 'resizing with fit-in type strategy' => [
			'fit-in',
			800,
			600,
			null,
			'fit-in/800x600/folder%2Fimage.png',
			'o8vUdcLSyN',
		];

		yield 'crop mappings convert width/height into target box endpoints' => [
			null,
			null,
			null,
			[150, 100, 20, 30], // width, height, horizontal (x), vertical (y)
			'20x30:170x130/folder%2Fimage.png', // x1xy1:x2xy2
			'MQ5ciKFT6N',
		];

		yield 'full combined transformations pipeline' => [
			'fit-in',
			120,
			120,
			[50, 50, 10, 10],
			'10x10:60x60/fit-in/120x120/folder%2Fimage.png',
			'n3txtT_DGg',
		];
	}
}
