<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\SirvUrlSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SirvUrlSourceTest extends UnitTestCase
{
	private SirvUrlSource $subject;

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new SirvUrlSource();
	}

	#[Test]
	#[DataProvider('urlParsingDataProvider')]
	public function buildPipelineCorrectlyFormatsVariousUrlStructures(string $inputUrl, string $expectedOutput): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn($inputUrl);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame($expectedOutput, $result);
	}

	public static function urlParsingDataProvider(): \Generator
	{
		yield 'leading slash is trimmed' => [
			'/fileadmin/assets/photo.jpg',
			'fileadmin/assets/photo.jpg',
		];

		yield 'domain structure is discarded from input path' => [
			'https://external-storage.com/subfolder/pic.jpg',
			'subfolder/pic.jpg',
		];

		yield 'query string appended via suffix' => [
			'/banner.jpg?w=800&q=80',
			'banner.jpg?w=800&q=80',
		];

		yield 'empty or missing query strings ignored cleanly' => [
			'/images/logo.png?',
			'images/logo.png',
		];
	}
}
