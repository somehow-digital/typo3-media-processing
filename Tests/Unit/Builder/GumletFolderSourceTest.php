<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\GumletFolderSource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GumletFolderSourceTest extends UnitTestCase
{
	private GumletFolderSource $subject;

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new GumletFolderSource();
		$this->resetSingletonInstances = true;
	}

	#[Test]
	#[DataProvider('getSourceDataProvider')]
	public function getSourceReturnsCorrectlyFormattedPath(?string $previewUrl, ?string $publicUrl, string $expected): void
	{
		$fileStub = $this->createStub(File::class);

		if ($previewUrl === null) {
			$fileStub
				->method('getPublicUrl')
				->willReturn($publicUrl);
		}

		// Ensure OnlineMediaUtility falls through cleanly to our data provider URL strings
		$registryStub = $this->createStub(OnlineMediaHelperRegistry::class);
		$registryStub
			->method('getOnlineMediaHelper')
			->willReturn($this->createConfiguredStub(OnlineMediaHelperInterface::class, [
				'getPreviewImage' => $previewUrl,
			]));

		GeneralUtility::setSingletonInstance(OnlineMediaHelperRegistry::class, $registryStub);

		$result = $this->subject->getSource($fileStub);

		$this->assertSame($expected, $result);
	}

	public static function getSourceDataProvider(): \Generator
	{
		yield 'Online media preview URL with query' => [
			'https://example.com/preview/image.jpg?foo=bar', // OnlineMediaUtility result
			null,                                            // file->getPublicUrl() result
			'preview/image.jpg?foo=bar',                      // Expected output
		];

		yield 'Fallback to local file public URL without query' => [
			null,
			'/fileadmin/user_upload/photo.jpg',
			'fileadmin/user_upload/photo.jpg',
		];

		yield 'Fallback to local file public URL with query' => [
			null,
			'https://cdn.domain.local/assets/doc.pdf?v=123',
			'assets/doc.pdf?v=123',
		];
	}
}
