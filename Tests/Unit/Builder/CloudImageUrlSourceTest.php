<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudImageUrlSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudImageUrlSourceTest extends UnitTestCase
{
	private CloudImageUrlSource $subject;
	private string $testHost = 'https://demo.cloudimage.io';

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new CloudImageUrlSource($this->testHost);
		$this->resetSingletonInstances = true;
	}

	#[Test]
	public function getSourceFallsBackToPublicUrlWhenPreviewImageIsMissing(): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getPublicUrl')
			->willReturn('https://cdn.example.com/fileadmin/image.png?v=2');

		// Mock the TYPO3 registry structure so OnlineMediaUtility securely returns null
		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn(null);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame('https://demo.cloudimage.io/fileadmin/image.png?v=2', $result);
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
			->willReturn('/var/www/html/public/typo3temp/assets/online_media/dailymotion-video.jpg');

		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn($helperStub);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		// Path should extract from /typo3temp and have its leading slash trimmed when prepended to the host
		$this->assertSame('https://demo.cloudimage.io/typo3temp/assets/online_media/dailymotion-video.jpg', $result);
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
			'https://demo.cloudimage.io/fileadmin/assets/photo.jpg',
		];

		yield 'domain structure is discarded from input path' => [
			'https://external-storage.com/subfolder/pic.jpg',
			'https://demo.cloudimage.io/subfolder/pic.jpg',
		];

		yield 'query string appended via suffix' => [
			'/banner.jpg?width=100&quality=high',
			'https://demo.cloudimage.io/banner.jpg?width=100&quality=high',
		];

		yield 'empty or missing query strings ignored cleanly' => [
			'/images/logo.png?',
			'https://demo.cloudimage.io/images/logo.png',
		];
	}
}
