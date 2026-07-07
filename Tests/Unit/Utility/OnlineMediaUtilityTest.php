<?php

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Utility\OnlineMediaUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OnlineMediaUtilityTest extends UnitTestCase
{
	protected function tearDown(): void
	{
		GeneralUtility::purgeInstances();
		parent::tearDown();
	}

	#[Test]
	public function getPreviewImageReturnsRelativePathWhenImageExists(): void
	{
		$fileStub = $this->createStub(File::class);

		// Mock the media helper to return a specific absolute path
		$helperMock = $this->createMock(OnlineMediaHelperInterface::class);
		$helperMock
			->expects($this->once())
			->method('getPreviewImage')
			->with($fileStub)
			->willReturn('/var/www/html/public/typo3temp/assets/online_media/example.jpg');

		// Mock the registry to return our helper
		$registryMock = $this->createMock(OnlineMediaHelperRegistry::class);
		$registryMock
			->expects($this->once())
			->method('getOnlineMediaHelper')
			->with($fileStub)
			->willReturn($helperMock);

		// Inject the registry mock into GeneralUtility
		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryMock);

		// Execute
		$result = OnlineMediaUtility::getPreviewImage($fileStub);

		// Assert that the path is correctly trimmed to start from /typo3temp
		$this->assertSame('/typo3temp/assets/online_media/example.jpg', $result);
	}

	#[Test]
	public function getPreviewImageReturnsNullWhenNoHelperFound(): void
	{
		// Registry returns null (no helper found for this file type)
		$registryMock = $this->createMock(OnlineMediaHelperRegistry::class);
		$registryMock
			->expects($this->once())
			->method('getOnlineMediaHelper')
			->willReturn(null);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryMock);

		$result = OnlineMediaUtility::getPreviewImage($this->createStub(File::class));

		$this->assertNull($result);
	}

	#[Test]
	public function getPreviewImageReturnsNullWhenHelperReturnsNull(): void
	{
		$fileStub = $this->createStub(File::class);

		$helperMock = $this->createMock(OnlineMediaHelperInterface::class);
		$helperMock
			->expects($this->once())
			->method('getPreviewImage')
			->willReturn(null);

		$registryMock = $this->createMock(OnlineMediaHelperRegistry::class);
		$registryMock
			->expects($this->once())
			->method('getOnlineMediaHelper')
			->willReturn($helperMock);

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryMock);

		$result = OnlineMediaUtility::getPreviewImage($fileStub);

		$this->assertNull($result);
	}
}
