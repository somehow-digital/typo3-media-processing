<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgLabBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgLabBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://assets.imglab-cdn.net';

	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new ImgLabBuilder(self::BASE_URL, 'a2V5', 'c2FsdA==');

		$builder->setSource('uploads/banner.png')
			->setMode('resize')
			->setWidth(1200)
			->setHeight(630)
			->setRegion(5, 10, 500, 400);

		$this->assertSame(self::BASE_URL, $builder->getEndpoint());
		$this->assertSame('a2V5', $builder->getKey());
		$this->assertSame('c2FsdA==', $builder->getSalt());
		$this->assertSame('uploads/banner.png', $builder->getSource());
		$this->assertSame('resize', $builder->getMode());
		$this->assertSame(1200, $builder->getWidth());
		$this->assertSame(630, $builder->getHeight());
		$this->assertSame([5, 10, 500, 400], $builder->getRegion());
	}

	#[Test]
	public function buildGeneratesUnsignedUrlWhenKeyIsNull(): void
	{
		$builder = new ImgLabBuilder(self::BASE_URL, null, null);
		$builder->setSource('gallery/photo.png')
			->setWidth(400)
			->setHeight(300);

		$expectedUrl = 'https://assets.imglab-cdn.net/gallery%2Fphoto.png?width=400&height=300';
		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	#[DataProvider('imgLabTransformationsDataProvider')]
	public function buildGeneratesCorrectlyOrderedAndSignedUrls(
		?string $mode,
		?int $width,
		?int $height,
		?array $region,
		string $expectedPathAndQuery,
		string $expectedSignature
	): void {
		// "key123" encoded in base64 is "a2V5MTIz"
		// "salt123" encoded in base64 is "c2FsdDEyMw=="
		$builder = new ImgLabBuilder(self::BASE_URL, 'a2V5MTIz', 'c2FsdDEyMw==');
		$builder->setSource('products/item.jpg');

		if ($mode !== null) {
			$builder->setMode($mode);
		}
		if ($width !== null) {
			$builder->setWidth($width);
		}
		if ($height !== null) {
			$builder->setHeight($height);
		}
		if ($region !== null) {
			$builder->setRegion(...$region);
		}

		$expectedUrl = sprintf(
			'https://assets.imglab-cdn.net/%s&signature=%s',
			$expectedPathAndQuery,
			$expectedSignature
		);

		$this->assertSame($expectedUrl, $builder->build());
	}

	public static function imgLabTransformationsDataProvider(): \Generator
	{
		yield 'basic sizing modifiers match standard structure' => [
			'crop',
			800,
			600,
			null,
			'products%2Fitem.jpg?mode=crop&width=800&height=600',
			'WcMBQ46QysHYRdaEhzQ7bCqUSH2vrbQC9XXY5OQLAKE', // Pre-calculated URL-safe base64 hmac snippet
		];

		yield 'region values compiled cleanly to string array sequence' => [
			null,
			null,
			null,
			[0, 50, 200, 200], // horizontal, vertical, width, height
			'products%2Fitem.jpg?region=0,50,200,200',
			'xpIlhoR-3e1m0H3B-k5Yj94KEt-8ScmNL3Gqg6VtGOg',
		];

		yield 'all processing constraints merged simultaneously' => [
			'force',
			300,
			300,
			[10, 10, 100, 100],
			'products%2Fitem.jpg?mode=force&width=300&height=300&region=10,10,100,100',
			'ouWJdAbH-N83HF6YenBq7Nr2eKffc-WZyHXrl4SftSQ',
		];
	}
}
