<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\GumletProxySource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GumletProxySourceTest extends UnitTestCase
{
	private GumletProxySource $subject;
	private string $testHost = 'https://example.gumlet.io';

	protected function setUp(): void
	{
		parent::setUp();
		$this->subject = new GumletProxySource($this->testHost);
		$this->resetSingletonInstances = true;
	}

	#[Test]
	#[DataProvider('proxyUrlDataProvider')]
	public function getSourceReturnsCorrectlyFormattedPath(?string $previewUrl, ?string $publicUrl, string $expected): void
	{
		$fileStub = $this->createStub(File::class);

		if ($previewUrl === null) {
			$fileStub
				->method('getPublicUrl')
				->willReturn($publicUrl);
		}

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

	public static function proxyUrlDataProvider(): \Iterator
	{
		yield 'Host with trailing slash, path with query' => [
			'https://example.com/images/pic.jpg?w=800&h=600',       // Preview URL string
			null,                                                    // Public URL string
			'fetch/https%3A%2F%2Fexample.gumlet.io%2Fimages%2Fpic.jpg%3Fw%3D800%26h%3D600', // Expected
		];

		yield 'Host without trailing slash, fallback to public URL' => [
			null,
			'/fileadmin/images/hero.png',
			'fetch/https%3A%2F%2Fexample.gumlet.io%2Ffileadmin%2Fimages%2Fhero.png',
		];
	}
}
