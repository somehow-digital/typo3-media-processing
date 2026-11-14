<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
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

	#[Test]
	public function buildReturnsRelativeUrlOnRelativeEndpoint(): void
	{
		$builder = new ImgProxyBuilder('/_assets/processed/', null, null, null, null);

		$builder->setSource('images/banner.jpg')
			->setWidth(1200)
			->setHeight(630)
			->setType('webp');

		$expectedUrl = '/_assets/processed/insecure/rt:webp/w:1200/h:630/plain/images/banner.jpg';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	#[DataProvider('sourcePathEncodingProvider')]
	public function buildEncodesSourcePath(string $source, string $expectedEncodedSource): void
	{
		$builder = new ImgProxyBuilder(self::BASE_URL, null, null, null, null);
		$url = $builder->setSource($source)->setWidth(100)->build();

		$this->assertStringEndsWith('/w:100/plain/' . $expectedEncodedSource, $url);
	}

	#[Test]
	public function buildSignsThePercentEncodedSourcePath(): void
	{
		$keyBinary = 'test-key-32-bytes-long-abcde12345';
		$saltBinary = 'test-salt-32-bytes-long-abcde1234';

		$builder = new ImgProxyBuilder(
			'https://imgproxy.example.com',
			bin2hex($keyBinary),
			bin2hex($saltBinary),
			32,
			null
		);

		$url = $builder
			->setSource('local:///user_upload/Gebäude/foto.jpg')
			->setWidth(200)
			->build();

		// The signature must cover the path exactly as it is requested (encoded)
		$path = '/w:200/plain/local:///user_upload/Geb%C3%A4ude/foto.jpg';
		$signature = rtrim(
			strtr(base64_encode(hash_hmac('sha256', $saltBinary . $path, $keyBinary, true)), '+/', '-_'),
			'='
		);

		$this->assertSame('https://imgproxy.example.com/' . $signature . $path, $url);
	}

	public static function sourcePathEncodingProvider(): iterable
	{
		yield 'plain ascii stays untouched' => [
			'images/photo.jpg',
			'images/photo.jpg',
		];

		yield 'unreserved characters stay untouched' => [
			'images/my-photo_v1.2~final.jpg',
			'images/my-photo_v1.2~final.jpg',
		];

		yield 'scheme and slashes are preserved' => [
			'local:///user_upload/photo.jpg',
			'local:///user_upload/photo.jpg',
		];

		yield 'umlaut is encoded as utf-8 bytes' => [
			'user_upload/Gebäude/foto.jpg',
			'user_upload/Geb%C3%A4ude/foto.jpg',
		];

		yield 'real world path with local scheme' => [
			'local:///user_upload/Bilder/02_Firma/Gebäude/firma_startseite_1.jpg',
			'local:///user_upload/Bilder/02_Firma/Geb%C3%A4ude/firma_startseite_1.jpg',
		];

		yield 'all german umlauts and sharp s' => [
			'äöüÄÖÜß.jpg',
			'%C3%A4%C3%B6%C3%BC%C3%84%C3%96%C3%9C%C3%9F.jpg',
		];

		yield 'decomposed umlaut (a + combining diaeresis)' => [
			"Gebau\u{0308}de.jpg",
			'Gebau%CC%88de.jpg',
		];

		yield 'multibyte character outside latin range' => [
			'preis-€.jpg',
			'preis-%E2%82%AC.jpg',
		];

		yield 'space is encoded' => [
			'my folder/my photo.jpg',
			'my%20folder/my%20photo.jpg',
		];

		yield 'hash is encoded' => [
			'images/photo#1.jpg',
			'images/photo%231.jpg',
		];

		yield 'unsafe url characters are encoded' => [
			'a"b<c>d`e{f}g.jpg',
			'a%22b%3Cc%3Ed%60e%7Bf%7Dg.jpg',
		];

		yield 'control characters are encoded' => [
			"a\tb\x7F.jpg",
			'a%09b%7F.jpg',
		];
	}
}
