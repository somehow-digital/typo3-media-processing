<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\BunnyBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BunnyBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://myzone.b-cdn.net';

	#[Test]
	public function gettersAndSettersPropertiesCanBeSetAndRetrieved(): void
	{
		$builder = new BunnyBuilder(self::BASE_URL, 'my-secret-key');

		$this->assertSame(self::BASE_URL, $builder->getEndpoint());
		$this->assertSame('my-secret-key', $builder->getKey());

		$this->assertSame($builder, $builder->setSource('images/photo.jpg'));
		$this->assertSame('images/photo.jpg', $builder->getSource());

		$this->assertSame($builder, $builder->setWidth(800));
		$this->assertSame(800, $builder->getWidth());

		$this->assertSame($builder, $builder->setHeight(600));
		$this->assertSame(600, $builder->getHeight());

		$this->assertSame($builder, $builder->setCrop(100, 200, 10, 20));
		$this->assertSame([100, 200, 10, 20], $builder->getCrop());
	}

	#[Test]
	public function setCropHandlesNegativeValuesByEnforcingZero(): void
	{
		$builder = new BunnyBuilder(self::BASE_URL, null);
		$builder->setCrop(-50, 100, -10, 0);

		$this->assertSame([0, 100, 0, 0], $builder->getCrop());
	}

	#[Test]
	public function buildReturnsUrlWithoutTokenWhenKeyIsMissing(): void
	{
		$builder = new BunnyBuilder('https://myzone.b-cdn.net/', null);

		$builder->setSource('/images/banner.jpg')
			->setWidth(1200)
			->setHeight(630)
			->setCrop(1200, 630, 0, 0);

		$expectedUrl = 'https://myzone.b-cdn.net/images%2Fbanner.jpg?crop=1200%2C630%2C0%2C0&height=630&width=1200';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildReturnsSignedUrlWhenKeyIsProvided(): void
	{
		$key = 'super-secret-bunny-key';
		$builder = new BunnyBuilder(self::BASE_URL, $key);

		$builder->setSource('images/avatar.png')
			->setWidth(200);

		$expectedExpiration = time() + BunnyBuilder::SIGNATURE_EXPIRATION;
		$url = $builder->build();

		$this->assertStringStartsWith('https://myzone.b-cdn.net/images%2Favatar.png?width=200&token=', $url);
		$this->assertStringContainsString('&expires=' . $expectedExpiration, $url);

		$data = implode('', [
			$key,
			'/images%2Favatar.png',
			$expectedExpiration,
			'width=200',
		]);
		$hash = hash('sha256', $data, true);
		$expectedSignature = str_replace('=', '', strtr(base64_encode($hash), '+/', '-_'));

		$this->assertStringContainsString('token=' . $expectedSignature, $url);
	}
}
