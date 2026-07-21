<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImagorFileSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImagorFileSourceTest extends UnitTestCase
{
	private ImagorFileSource $subject;

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new ImagorFileSource();
		$this->resetSingletonInstances = true;
	}

	#[Test]
	public function getSourceFallsBackToIdentifierWhenPreviewImageIsMissing(): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getIdentifier')
			->willReturn('/user_upload/images/photo.png');

		// Mock the TYPO3 registry structure so OnlineMediaUtility returns null
		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn(null);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame('user_upload/images/photo.png', $result);
	}

	#[Test]
	public function getSourceFavorsPreviewImageWhenRegistryFindsValidHelper(): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getIdentifier')
			->willReturn('/fallback-identifier.jpg');

		$helperStub = $this->createStub(OnlineMediaHelperInterface::class);
		$helperStub
			->method('getPreviewImage')
			->willReturn('/var/www/html/public/typo3temp/assets/online_media/youtube-video.jpg');

		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn($helperStub);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame('typo3temp/assets/online_media/youtube-video.jpg', $result);
	}

	#[Test]
	#[DataProvider('urlParsingDataProvider')]
	public function buildPipelineCorrectlyFormatsVariousUrlStructures(string $inputUrl, string $expectedOutput): void
	{
		$fileStub = $this->createStub(File::class);
		$fileStub
			->method('getIdentifier')
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
			'fileadmin/assets/photo.jpg',
		];

		yield 'domain structure is discarded from input path if present' => [
			'https://external-storage.com/subfolder/pic.jpg',
			'subfolder/pic.jpg',
		];

		yield 'query string appended via suffix' => [
			'/banner.jpg?width=100&quality=high',
			'banner.jpg?width=100&quality=high',
		];

		yield 'empty or missing query strings ignored cleanly' => [
			'/images/logo.png?',
			'images/logo.png',
		];
	}
}
