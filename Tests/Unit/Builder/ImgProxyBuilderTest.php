<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgProxyBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgProxyBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'http://localhost:8080';

	#[Test]
	public function gettersAndSettersPropertiesCanBeSetAndRetrieved(): void
	{
		$builder = new ImgProxyBuilder(self::BASE_URL, null, null, null, null);

		$this->assertSame($builder, $builder->setSource('images/photo.jpg'));
		$this->assertSame('images/photo.jpg', $builder->getSource());

		$this->assertSame($builder, $builder->setType('png'));
		$this->assertSame('png', $builder->getType());

		$this->assertSame($builder, $builder->setGravity(ImgProxyBuilder::GRAVITY_CENTER, 0.5, 0.5));
		$this->assertSame([ImgProxyBuilder::GRAVITY_CENTER, 0.5, 0.5], $builder->getGravity());

		$this->assertSame($builder, $builder->setWidth(800));
		$this->assertSame(800, $builder->getWidth());

		$this->assertSame($builder, $builder->setMinWidth(400));
		$this->assertSame(400, $builder->getMinWidth());

		$this->assertSame($builder, $builder->setHeight(600));
		$this->assertSame(600, $builder->getHeight());

		$this->assertSame($builder, $builder->setMinHeight(300));
		$this->assertSame(300, $builder->getMinHeight());

		$this->assertSame($builder, $builder->setCrop(100, 100, [ImgProxyBuilder::GRAVITY_TOP]));
		$this->assertSame([100, 100, ImgProxyBuilder::GRAVITY_TOP], $builder->getCrop());

		$this->assertSame($builder, $builder->setDevicePixelRatio(2.0));
		$this->assertSame(2.0, $builder->getDevicePixelRatio());

		$this->assertSame($builder, $builder->setHash('cachebuster123'));
		$this->assertSame('cachebuster123', $builder->getHash());
	}

	#[Test]
	public function buildReturnsInsecureUrlWhenSignatureCredentialsAreMissing(): void
	{
		$builder = new ImgProxyBuilder('http://localhost:8080/', null, null, null, null);

		$builder->setSource('images/banner.jpg')
			->setWidth(1200)
			->setHeight(630)
			->setType('webp');

		$expectedUrl = 'http://localhost:8080/insecure/rt:webp/w:1200/h:630/plain/images/banner.jpg';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildReturnsSecureUrlWhenSignatureCredentialsArePassed(): void
	{
		// Example keys (Hex encoded)
		$key = bin2hex('test-key-32-bytes-long-abcde12345');
		$salt = bin2hex('test-salt-32-bytes-long-abcde1234');

		$builder = new ImgProxyBuilder('https://imgproxy.example.com', $key, $salt, 32, null);

		$builder->setSource('avatar.png')
			->setWidth(200);

		$url = $builder->build();

		// Ensure the base structure is correct and it doesn't contain 'insecure'
		$this->assertStringStartsWith('https://imgproxy.example.com/', $url);
		$this->assertStringNotContainsString('insecure', $url);
		$this->assertStringEndsWith('/w:200/plain/avatar.png', $url);
	}

	#[Test]
	public function buildReturnsEncryptedSourceUrlWhenSecretIsProvided(): void
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('The openssl extension is not available.');
		}

		$secret = 'super-secret-encryption-key-1234';
		$builder = new ImgProxyBuilder(self::BASE_URL, null, null, null, $secret);

		$builder->setSource('private/photo.jpg');
		$url = $builder->build();

		$this->assertStringContainsString('/enc/', $url);
		$this->assertStringNotContainsString('private/photo.jpg', $url);
	}
}
