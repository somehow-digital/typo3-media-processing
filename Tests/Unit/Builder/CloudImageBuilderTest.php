<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudImageBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudImageBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://demo.cloudimage.io';

	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new CloudImageBuilder(self::BASE_URL, 'secret_key');

		$builder->setSource('images/banner.jpg')
			->setFunction('crop')
			->setWidth(1200)
			->setHeight(630)
			->setCrop(100, 150, 10, 20);

		$this->assertSame('images/banner.jpg', $builder->getSource());
		$this->assertSame('crop', $builder->getFunction());
		$this->assertSame(1200, $builder->getWidth());
		$this->assertSame(630, $builder->getHeight());
		$this->assertSame([100, 150, 10, 20], $builder->getCrop());
	}

	#[Test]
	public function buildGeneratesUnsignedUrlWhenKeyIsNull(): void
	{
		$builder = new CloudImageBuilder(self::BASE_URL, null);
		$builder->setSource('folder/image.png')
			->setWidth(300)
			->setHeight(200);

		$expectedUrl = 'https://demo.cloudimage.io/folder%2Fimage.png?w=300&h=200';
		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	#[DataProvider('cloudImageTransformationsDataProvider')]
	public function buildGeneratesCorrectlyOrderedAndSignedUrls(
		?string $function,
		?int $width,
		?int $height,
		?array $crop,
		string $expectedPathAndQuery,
		string $expectedSignature
	): void {
		$builder = new CloudImageBuilder('https://test.cloudimage.io', 'test-signing-key');
		$builder->setSource('gallery/photo.png');

		if ($function !== null) {
			$builder->setFunction($function);
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

		$expectedUrl = sprintf(
			'https://test.cloudimage.io/%s&ci_sign=%s',
			$expectedPathAndQuery,
			$expectedSignature
		);

		$this->assertSame($expectedUrl, $builder->build());
	}

	public static function cloudImageTransformationsDataProvider(): \Generator
	{
		yield 'basic scaling parameters' => [
			'bound',
			800,
			600,
			null,
			'gallery%2Fphoto.png?func=bound&w=800&h=600',
			'e7b24f9223318a4c89a1cfefdd46b949110ec3ad', // Pre-calculated sha1 hash
		];

		yield 'crop options mapping via box bounding calculations' => [
			null,
			null,
			null,
			[100, 150, 10, 20], // width, height, horizontal (x offset), vertical (y offset)
			'gallery%2Fphoto.png?tl_px=100,150&br_px=110,170', // tl_px = [w, h], br_px = [w + x, h + y]
			'94e55ea7c405abd591bbc465714d91c2df0ddb12',
		];

		yield 'all options active synchronously' => [
			'crop',
			400,
			400,
			[50, 50, 5, 5],
			'gallery%2Fphoto.png?func=crop&w=400&h=400&tl_px=50,50&br_px=55,55',
			'c318669e2a22f621cb3a482800a97e0fb6c3874c',
		];
	}
}
