<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgLabProxySource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgLabProxySourceTest extends UnitTestCase
{
	private ImgLabProxySource $subject;
	private string $testHost = 'https://assets.imglab-cdn.net/v1';

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new ImgLabProxySource($this->testHost);
		$this->resetSingletonInstances = true;
	}

	#[Test]
	public function getHostReturnsConfiguredHost(): void
	{
		$this->assertSame($this->testHost, $this->subject->getHost());
	}

	#[Test]
	public function getSourceFallsBackToPublicUrlWhenPreviewImageIsMissing(): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn('https://cdn.example.com/fileadmin/image.png?v=2');

		// Mock the TYPO3 registry structure so OnlineMediaUtility returns null
		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn(null);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame('https://assets.imglab-cdn.net/v1/fileadmin/image.png?v=2', $result);
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

		$this->assertSame('https://assets.imglab-cdn.net/v1/typo3temp/assets/online_media/vimeo-video.jpg', $result);
	}

	#[Test]
	#[DataProvider('urlParsingDataProvider')]
	public function buildPipelineCorrectlyFormatsVariousUrlStructures(string $inputUrl, string $expectedOutput): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn($inputUrl);

		// Ensure OnlineMediaUtility falls through cleanly to our data provider URL strings
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
			'https://assets.imglab-cdn.net/v1/fileadmin/assets/photo.jpg',
		];

		yield 'domain structure is discarded from input path' => [
			'https://external-storage.com/subfolder/pic.jpg',
			'https://assets.imglab-cdn.net/v1/subfolder/pic.jpg',
		];

		yield 'query string appended via suffix' => [
			'/banner.jpg?dpr=2&width=500',
			'https://assets.imglab-cdn.net/v1/banner.jpg?dpr=2&width=500',
		];

		yield 'empty or missing query strings ignored cleanly' => [
			'/images/logo.png?',
			'https://assets.imglab-cdn.net/v1/images/logo.png',
		];
	}
}
