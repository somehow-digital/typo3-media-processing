<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgixProxySource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgixProxySourceTest extends UnitTestCase
{
	private ImgixProxySource $subject;
	private string $testHost = 'https://my-source.imgix.net';

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new ImgixProxySource($this->testHost);
		$this->resetSingletonInstances = true;
	}

	#[Test]
	public function getSourceFallsBackToPublicUrlWhenPreviewImageIsMissing(): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn('https://cdn.example.com/fileadmin/image.png?v=2');

		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn(null);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame('https://my-source.imgix.net/fileadmin/image.png?v=2', $result);
	}

	#[Test]
	public function getSourceFavorsPreviewImageWhenRegistryFindsValidHelper(): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn('/fallback-path.jpg');

		$helperStub = $this->createStub(OnlineMediaHelperInterface::class);
		$helperStub
			->method('getPreviewImage')
			->willReturn('/var/www/html/public/typo3temp/assets/online_media/vimeo-video.jpg');

		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn($helperStub);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame('https://my-source.imgix.net/typo3temp/assets/online_media/vimeo-video.jpg', $result);
	}

	#[Test]
	#[DataProvider('urlParsingDataProvider')]
	public function buildPipelineCorrectlyFormatsVariousUrlStructures(string $inputUrl, string $expectedOutput): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn($inputUrl);

		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn(null);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame($expectedOutput, $result);
	}

	public static function urlParsingDataProvider(): \Generator
	{
		yield 'leading slash is trimmed' => [
			'/fileadmin/assets/photo.jpg',
			'https://my-source.imgix.net/fileadmin/assets/photo.jpg',
		];

		yield 'domain structure is discarded from input path' => [
			'https://external-storage.com/subfolder/pic.jpg',
			'https://my-source.imgix.net/subfolder/pic.jpg',
		];

		yield 'query string appended via suffix' => [
			'/banner.jpg?auto=format,compress',
			'https://my-source.imgix.net/banner.jpg?auto=format,compress',
		];

		yield 'empty or missing query strings ignored cleanly' => [
			'/images/logo.png?',
			'https://my-source.imgix.net/images/logo.png',
		];
	}
}
