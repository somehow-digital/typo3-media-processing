<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgixBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgixBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://my-source.imgix.net';

	#[Test]
	public function gettersAndSettersFunctionCorrectly(): void
	{
		$builder = new ImgixBuilder(self::BASE_URL, 'my-secure-token');

		$builder->setSource('uploads/avatar.png')
			->setFit('crop')
			->setWidth(640)
			->setMaxWidth(1024)
			->setHeight(480)
			->setMaxHeight(768)
			->setRect(10, 20, 300, 250);

		$this->assertSame(self::BASE_URL, $builder->getEndpoint());
		$this->assertSame('my-secure-token', $builder->getKey());
		$this->assertSame('uploads/avatar.png', $builder->getSource());
		$this->assertSame('crop', $builder->getFit());
		$this->assertSame(640, $builder->getWidth());
		$this->assertSame(1024, $builder->getMaxWidth());
		$this->assertSame(480, $builder->getHeight());
		$this->assertSame(768, $builder->getMaxHeight());
		$this->assertSame([10, 20, 300, 250], $builder->getRect());
	}

	#[Test]
	public function buildGeneratesUnsignedUrlWhenKeyIsNull(): void
	{
		$builder = new ImgixBuilder(self::BASE_URL, null);
		$builder->setSource('images/pic.jpg')
			->setWidth(200)
			->setHeight(100);

		// Parameters should be alphabetized via ksort: h=100 then w=200
		$expectedUrl = 'https://my-source.imgix.net/images%2Fpic.jpg?h=100&w=200';
		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	#[DataProvider('imgixTransformationsDataProvider')]
	public function buildGeneratesCorrectlyOrderedAndSignedUrls(
		?string $fit,
		?int $w,
		?int $maxW,
		?int $h,
		?int $maxH,
		?array $rect,
		string $expectedPathAndQuery,
		string $expectedSignature
	): void {
		// Secure token combined with standard endpoints
		$builder = new ImgixBuilder(self::BASE_URL, 'secure-signing-key');
		$builder->setSource('portfolio/item 1.png');

		if ($fit !== null) {
			$builder->setFit($fit);
		}
		if ($w !== null) {
			$builder->setWidth($w);
		}
		if ($maxW !== null) {
			$builder->setMaxWidth($maxW);
		}
		if ($h !== null) {
			$builder->setHeight($h);
		}
		if ($maxH !== null) {
			$builder->setMaxHeight($maxH);
		}
		if ($rect !== null) {
			$builder->setRect(...$rect);
		}

		$expectedUrl = sprintf(
			'https://my-source.imgix.net/%s&s=%s',
			$expectedPathAndQuery,
			$expectedSignature
		);

		$this->assertSame($expectedUrl, $builder->build());
	}

	public static function imgixTransformationsDataProvider(): \Generator
	{
		yield 'basic scaling parameters sorted alphabetically' => [
			null,
			800,
			null,
			600,
			null,
			null,
			'portfolio%2Fitem%201.png?h=600&w=800',
			'003ddb55c38d677cd5d91f589782423e', // Pre-calculated md5 signature
		];

		yield 'max limits and fitting rules applied together' => [
			'clamp',
			null,
			1200,
			null,
			900,
			null,
			'portfolio%2Fitem%201.png?fit=clamp&max-h=900&max-w=1200',
			'da933d05c3459d8fe95a9db97eb39b81',
		];

		yield 'rect crop values passed smoothly as comma array string' => [
			null,
			null,
			null,
			null,
			null,
			[50, 50, 400, 300], // horizontal, vertical, width, height
			'portfolio%2Fitem%201.png?rect=50%2C50%2C400%2C300',
			'989ca4921cc66f48a88e2debbb3e60a0',
		];

		yield 'everything combined ensures full ksort optimization' => [
			'face',
			400,
			500,
			300,
			400,
			[0, 0, 100, 100],
			'portfolio%2Fitem%201.png?fit=face&h=300&max-h=400&max-w=500&rect=0%2C0%2C100%2C100&w=400',
			'da28c48a69db3445bc3093eee12d4b07',
		];
	}
}
