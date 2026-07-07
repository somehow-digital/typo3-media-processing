<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\OptimoleBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OptimoleBuilderTest extends UnitTestCase
{
	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new OptimoleBuilder('myapikey123');

		$builder->setSource('images/hero.png')
			->setType('resize')
			->setGravity(OptimoleBuilder::GRAVITY_CENTER, 10, 20)
			->setWidth(1920)
			->setMinWidth(1024)
			->setHeight(1080)
			->setMinHeight(768)
			->setCrop(400, 300, [OptimoleBuilder::GRAVITY_SMART])
			->setHash('abcdef');

		$this->assertSame('images/hero.png', $builder->getSource());
		$this->assertSame('resize', $builder->getType());
		$this->assertSame([OptimoleBuilder::GRAVITY_CENTER, 10, 20], $builder->getGravity());
		$this->assertSame(1920, $builder->getWidth());
		$this->assertSame(1024, $builder->getMinWidth());
		$this->assertSame(1080, $builder->getHeight());
		$this->assertSame(768, $builder->getMinHeight());
		$this->assertSame([400, 300, OptimoleBuilder::GRAVITY_SMART], $builder->getCrop());
		$this->assertSame('abcdef', $builder->getHash());
	}

	#[Test]
	#[DataProvider('optimoleTransformationsDataProvider')]
	public function buildGeneratesCorrectOptimoleUrlStructure(
		?string $type,
		?array $gravityArgs,
		?int $w,
		?int $mw,
		?int $h,
		?int $mh,
		?array $cropArgs,
		?string $hash,
		string $expectedPath
	): void {
		$builder = new OptimoleBuilder('demo-key');
		$builder->setSource('/uploads/media/test.jpg');

		if ($type !== null) {
			$builder->setType($type);
		}
		if ($gravityArgs !== null) {
			$builder->setGravity(...$gravityArgs);
		}
		if ($w !== null) {
			$builder->setWidth($w);
		}
		if ($mw !== null) {
			$builder->setMinWidth($mw);
		}
		if ($h !== null) {
			$builder->setHeight($h);
		}
		if ($mh !== null) {
			$builder->setMinHeight($mh);
		}
		if ($cropArgs !== null) {
			$builder->setCrop(...$cropArgs);
		}
		if ($hash !== null) {
			$builder->setHash($hash);
		}

		$expectedUrl = 'https://demo-key.i.optimole.com/' . $expectedPath;
		$this->assertSame($expectedUrl, $builder->build());
	}

	public static function optimoleTransformationsDataProvider(): \Generator
	{
		yield 'basic scaling parameters' => [
			'resize',
			null,
			800,
			null,
			600,
			null,
			null,
			null,
			'rt:resize/w:800/h:600/uploads/media/test.jpg',
		];

		yield 'min bounds and hash caching buster applied' => [
			null,
			null,
			null,
			400,
			null,
			300,
			null,
			'hashval99',
			'mw:400/mh:300/cb:hashval99/uploads/media/test.jpg',
		];

		yield 'gravity parameters with optional offsets' => [
			null,
			[OptimoleBuilder::GRAVITY_TOP_LEFT, 50, null], // filter out null values automatically
			null,
			null,
			null,
			null,
			null,
			null,
			'g:nowe:50/uploads/media/test.jpg',
		];

		yield 'crop options mapped cleanly using array structures' => [
			null,
			null,
			null,
			null,
			null,
			null,
			[200, 200, [OptimoleBuilder::GRAVITY_CENTER]],
			null,
			'c:200:200:ce/uploads/media/test.jpg',
		];

		yield 'all options active synchronously' => [
			'fit',
			[OptimoleBuilder::GRAVITY_SMART, 10, 10],
			1200,
			800,
			900,
			600,
			[500, 400, [OptimoleBuilder::GRAVITY_BOTTOM_RIGHT]],
			'xyz',
			'rt:fit/g:sm:10:10/w:1200/mw:800/h:900/mh:600/c:500:400:soea/cb:xyz/uploads/media/test.jpg',
		];
	}
}
