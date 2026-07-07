<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\SirvBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SirvBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://demo.sirv.com';

	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new SirvBuilder(self::BASE_URL);

		$builder->setSource('images/product.jpg')
			->setScale('fit')
			->setWidth(800)
			->setHeight(600)
			->setCrop(400, 300, 10, 20);

		$this->assertSame('images/product.jpg', $builder->getSource());
		$this->assertSame('fit', $builder->getScale());
		$this->assertSame(800, $builder->getWidth());
		$this->assertSame(600, $builder->getHeight());
		$this->assertSame([400, 300, 10, 20], $builder->getCrop());
	}

	#[Test]
	#[DataProvider('sirvTransformationsDataProvider')]
	public function buildGeneratesCorrectSirvUrlStructure(
		?string $scale,
		?int $width,
		?int $height,
		?array $crop,
		string $expectedQueryString
	): void {
		$builder = new SirvBuilder(self::BASE_URL);
		$builder->setSource('/assets/test-image.png');

		if ($scale !== null) {
			$builder->setScale($scale);
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

		$expectedUrl = 'https://demo.sirv.com/assets%2Ftest-image.png' . $expectedQueryString;
		$this->assertSame($expectedUrl, $builder->build());
	}

	public static function sirvTransformationsDataProvider(): \Generator
	{
		yield 'only scaling options' => [
			'ignore',
			500,
			400,
			null,
			'?scale.option=ignore&w=500&h=400',
		];

		yield 'only crop coordinates mapping to cw, ch, cx, cy' => [
			null,
			null,
			null,
			[300, 200, 15, 25], // width, height, horizontal (x), vertical (y)
			'?cw=300&ch=200&cx=15&cy=25',
		];

		yield 'all parameters joined sequentially' => [
			'fit',
			1024,
			768,
			[800, 600, 0, 0],
			'?scale.option=fit&w=1024&h=768&cw=800&ch=600',
		];

		yield 'no query options when none provided' => [
			null,
			null,
			null,
			null,
			'?',
		];
	}
}
