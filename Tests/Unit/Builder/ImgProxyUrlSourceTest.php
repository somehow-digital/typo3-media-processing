<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgProxyUrlSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgProxyUrlSourceTest extends UnitTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'] = [];
		$this->resetSingletonInstances = true;
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']);

		parent::tearDown();
	}

	#[Test]
	public function getSourceReturnsUrlPrefixedWithHostWhenPublicUrlIsProvided(): void
	{
		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getPublicUrl')
			->willReturn('/fileadmin/user_upload/photo.jpg');

		$source = new ImgProxyUrlSource('https://cdn.example.com');
		$result = $source->getSource($fileMock);

		$this->assertSame('https://cdn.example.com/fileadmin/user_upload/photo.jpg', $result);
	}

	#[Test]
	#[DataProvider('urlParsingDataProvider')]
	public function getSourceCorrectlyTrimsAndFormatsUrlsWithVariousStructures(
		string $host,
		string $inputUrl,
		string $expectedOutput
	): void {
		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getPublicUrl')
			->willReturn($inputUrl);

		$source = new ImgProxyUrlSource($host);
		$result = $source->getSource($fileMock);

		$this->assertSame($expectedOutput, $result);
	}

	public static function urlParsingDataProvider(): \Generator
	{
		yield 'trailing slash on host and leading slash on path are normalized' => [
			'https://cdn.example.com/',
			'/images/photo.jpg',
			'https://cdn.example.com/images/photo.jpg',
		];

		yield 'no slashes on boundaries are normalized' => [
			'https://cdn.example.com',
			'images/photo.jpg',
			'https://cdn.example.com/images/photo.jpg',
		];

		yield 'query parameters are preserved cleanly' => [
			'https://cdn.example.com',
			'/images/photo.jpg?token=abc123_456&width=auto',
			'https://cdn.example.com/images/photo.jpg?token=abc123_456&width=auto',
		];

		yield 'full external absolute public url parses path out correctly' => [
			'https://cdn.example.com',
			'https://another-domain.com/assets/banner.png?v=2',
			'https://cdn.example.com/assets/banner.png?v=2',
		];

		yield 'empty query symbol is ignored' => [
			'https://cdn.example.com',
			'/images/photo.jpg?',
			'https://cdn.example.com/images/photo.jpg',
		];
	}
}
