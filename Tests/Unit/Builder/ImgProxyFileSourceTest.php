<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgProxyFileSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgProxyFileSourceTest extends UnitTestCase
{
	private ImgProxyFileSource $subject;

	protected function setUp(): void
	{
		parent::setUp();

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'] = [];
		$this->subject = new ImgProxyFileSource();
		$this->resetSingletonInstances = true;
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']);

		parent::tearDown();
	}

	#[Test]
	public function getSourceReturnsLocalProtocolWithCleanedPathWhenFileIdentifierIsProvided(): void
	{
		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getIdentifier')
			->willReturn('/user_upload/images/pic.jpg');

		$result = $this->subject->getSource($fileMock);

		$this->assertSame('local:///user_upload/images/pic.jpg', $result);
	}

	#[Test]
	#[DataProvider('urlParsingDataProvider')]
	public function getSourceCorrectlyParsesAndFormatsUrlsWithVariousStructures(string $inputUrl, string $expectedOutput): void
	{
		$fileMock = $this->createMock(File::class);
		$fileMock
			->expects($this->once())
			->method('getIdentifier')
			->willReturn($inputUrl);

		$result = $this->subject->getSource($fileMock);

		$this->assertSame($expectedOutput, $result);
	}

	public static function urlParsingDataProvider(): \Generator
	{
		yield 'leading slash is trimmed' => [
			'/path/to/file.png',
			'local:///path/to/file.png',
		];

		yield 'no leading slash' => [
			'path/to/file.png',
			'local:///path/to/file.png',
		];

		yield 'with query parameters' => [
			'/path/to/file.png?v=123&size=large',
			'local:///path/to/file.png?v=123&size=large',
		];

		yield 'full absolute url fallback' => [
			'https://example.com/sys_file/processed/image.jpg?foo=bar',
			'local:///sys_file/processed/image.jpg?foo=bar',
		];

		yield 'empty query string ignored' => [
			'/path/to/file.png?',
			'local:///path/to/file.png',
		];
	}
}
