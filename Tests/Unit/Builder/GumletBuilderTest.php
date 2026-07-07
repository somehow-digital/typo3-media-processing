<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\GumletBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GumletBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://demo.gumlet.io';

	#[Test]
	public function gettersAndSettersPropertiesCanBeSetAndRetrieved(): void
	{
		$builder = new GumletBuilder(self::BASE_URL, 'secret-key');

		$this->assertSame(self::BASE_URL, $builder->getEndpoint());
		$this->assertSame('secret-key', $builder->getKey());

		$this->assertSame($builder, $builder->setSource('images/photo.jpg'));
		$this->assertSame('images/photo.jpg', $builder->getSource());

		$this->assertSame($builder, $builder->setWidth(800));
		$this->assertSame(800, $builder->getWidth());

		$this->assertSame($builder, $builder->setHeight(600));
		$this->assertSame(600, $builder->getHeight());

		// Note GumletBuilder rearranges parameters internally to [horizontal, vertical, width, height]
		$this->assertSame($builder, $builder->setCrop(300, 200, 10, 20));
		$this->assertSame([10, 20, 300, 200], $builder->getCrop());

		$this->assertSame($builder, $builder->setGravity(0.3, 0.6));
		$this->assertSame([0.3, 0.6], $builder->getGravity());
	}

	#[Test]
	public function setCropHandlesNegativeValuesByEnforcingZero(): void
	{
		$builder = new GumletBuilder(self::BASE_URL, null);
		$builder->setCrop(-300, 200, -10, 0);

		$this->assertSame([0, 0, 0, 200], $builder->getCrop());
	}

	#[Test]
	public function buildReturnsUrlWithExtractWhenCropIsSetWithoutGravity(): void
	{
		$builder = new GumletBuilder('https://demo.gumlet.io/', null);

		$builder->setSource('/assets/cover.png')
			->setCrop(800, 400, 50, 60)
			->setWidth(400);

		// Expect extract mapping: horizontal, vertical, width, height
		$expectedUrl = 'https://demo.gumlet.io/assets/cover.png?extract=50,60,800,400&w=400';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildReturnsFocalPointParametersWhenGravityIsPresent(): void
	{
		$builder = new GumletBuilder(self::BASE_URL, null);

		$builder->setSource('assets/cover.png')
			->setCrop(800, 400, 50, 60) // This configuration should be ignored due to gravity overrides
			->setGravity(0.25, 0.75)
			->setHeight(300);

		$expectedUrl = 'https://demo.gumlet.io/assets/cover.png?mode=crop&crop=focalpoint&fp-x=0.25&fp-y=0.75&h=300';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildReturnsSignedUrlWhenKeyIsProvided(): void
	{
		$key = 'my-gumlet-secure-token';
		$builder = new GumletBuilder(self::BASE_URL, $key);

		$builder->setSource('product.jpg')
			->setWidth(500);

		$url = $builder->build();

		// Verify base signature layout structure
		$this->assertStringStartsWith('https://demo.gumlet.io/product.jpg?w=500&s=', $url);

		// Programmatically recalculate internal MD5 hashing step
		$expectedPath = 'product.jpg?w=500';
		$data = $key . '/' . $expectedPath;
		$expectedSignature = base64_encode(hash('md5', $data, true));

		$this->assertStringEndsWith('&s=' . $expectedSignature, $url);
	}
}
