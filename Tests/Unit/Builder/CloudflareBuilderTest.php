<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudflareBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudflareBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://example.com/cdn-cgi/image';

	#[Test]
	public function gettersAndSettersPropertiesCanBeSetAndRetrieved(): void
	{
		$builder = new CloudflareBuilder(self::BASE_URL);

		$this->assertSame($builder, $builder->setSource('images/photo.jpg'));
		$this->assertSame('images/photo.jpg', $builder->getSource());

		$this->assertSame($builder, $builder->setFit('contain'));
		$this->assertSame('contain', $builder->getFit());

		$this->assertSame($builder, $builder->setWidth(800));
		$this->assertSame(800, $builder->getWidth());

		$this->assertSame($builder, $builder->setHeight(600));
		$this->assertSame(600, $builder->getHeight());

		$this->assertSame($builder, $builder->setTrim(10, 20, 30, 40));
		$this->assertSame([10, 20, 30, 40], $builder->getTrim());

		$this->assertSame($builder, $builder->setGravity(0.5, 0.75));
		$this->assertSame([0.5, 0.75], $builder->getGravity());
	}

	#[Test]
	public function setTrimHandlesNegativeValuesByEnforcingZero(): void
	{
		$builder = new CloudflareBuilder(self::BASE_URL);
		$builder->setTrim(-10, 5, -20, 0);

		$this->assertSame([0, 5, 0, 0], $builder->getTrim());
	}

	#[Test]
	public function buildReturnsFormattedUrlWithOptionsAndSource(): void
	{
		$builder = new CloudflareBuilder('https://example.com/cdn-cgi/image/');

		$builder->setSource('/uploads/hero.png')
			->setFit('cover')
			->setWidth(1920)
			->setHeight(1080)
			->setTrim(5, 10, 5, 10)
			->setGravity(0.5, 0.5);

		// Expects Cloudflare format:
		// - Options comma-separated (fit=cover,width=1920,height=1080,trim=5;10;5;10,gravity=0.5x0.5)
		// - Trim values separated by semicolons
		// - Gravity values separated by 'x'
		// - A trailing slash between options block and the URL-encoded source
		$expectedUrl = 'https://example.com/cdn-cgi/image/fit=cover,width=1920,height=1080,trim=5;10;5;10,gravity=0.5x0.5/uploads%2Fhero.png';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildOmitsEmptyParameters(): void
	{
		$builder = new CloudflareBuilder(self::BASE_URL);

		$builder->setSource('images/minimal.jpg')
			->setWidth(300);

		$expectedUrl = 'https://example.com/cdn-cgi/image/width=300/images%2Fminimal.jpg';

		$this->assertSame($expectedUrl, $builder->build());
	}
}
