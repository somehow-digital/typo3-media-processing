<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ThumborUrlSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ThumborUrlSourceTest extends UnitTestCase
{
	private ThumborUrlSource $subject;
	private string $testHost = 'https://thumbor.my-domain.com';

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new ThumborUrlSource($this->testHost);
		$this->resetSingletonInstances = true;
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

		$this->assertSame('https://thumbor.my-domain.com/fileadmin/image.png?v=2', $result);
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

		$this->assertSame('https://thumbor.my-domain.com/typo3temp/assets/online_media/vimeo-video.jpg', $result);
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
			'https://thumbor.my-domain.com/fileadmin/assets/photo.jpg',
		];

		yield 'domain structure is discarded from input path' => [
			'https://external-storage.com/subfolder/pic.jpg',
			'https://thumbor.my-domain.com/subfolder/pic.jpg',
		];

		yield 'query string appended via suffix' => [
			'/banner.jpg?unsafe=true',
			'https://thumbor.my-domain.com/banner.jpg?unsafe=true',
		];

		yield 'empty or missing query strings ignored cleanly' => [
			'/images/logo.png?',
			'https://thumbor.my-domain.com/images/logo.png',
		];
	}
}
