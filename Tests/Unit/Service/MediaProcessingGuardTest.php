<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use SomehowDigital\Typo3\MediaProcessing\Service\MediaProcessingGuard;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MediaProcessingGuardTest extends UnitTestCase
{
	private ProviderInterface&MockObject $providerMock;
	private ExtensionConfiguration&MockObject $extensionConfigurationMock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->providerMock = $this->createMock(ProviderInterface::class);
		$this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['TYPO3_REQUEST']);
		parent::tearDown();
	}

	/**
	 * @param array $config Extension configuration array mock
	 * @param string|null $applicationType 'backend', 'frontend', or null
	 * @param bool $isStorageOnline
	 * @param bool $isStoragePublic
	 * @param bool $fileExists
	 * @param bool $providerHasConfig
	 * @param string|null $publicUrl
	 * @param bool $expectedResult
	 */
	#[DataProvider('isProcessingAllowedDataProvider')]
	public function testIsProcessingAllowed(
		array   $config,
		?string $applicationType,
		bool    $isStorageOnline,
		bool    $isStoragePublic,
		bool    $fileExists,
		bool    $providerHasConfig,
		?string $publicUrl,
		bool    $expectedResult
	): void
	{
		// Mock $GLOBALS['TYPO3_REQUEST'] with application type attribute if set
		if ($applicationType !== null) {
			$request = new ServerRequest();
			$type = match ($applicationType) {
				'backend' => SystemEnvironmentBuilder::REQUESTTYPE_BE,
				'frontend' => SystemEnvironmentBuilder::REQUESTTYPE_FE,
			};

			$request = $request->withAttribute('applicationType', $type);
			$GLOBALS['TYPO3_REQUEST'] = $request;
		}

		$this->extensionConfigurationMock
			->method('get')
			->with('media_processing')
			->willReturn($config);

		$this->providerMock
			->method('hasConfiguration')
			->willReturn($providerHasConfig);

		$storageMock = $this->createMock(ResourceStorage::class);
		$storageMock->method('isOnline')->willReturn($isStorageOnline);
		$storageMock->method('isPublic')->willReturn($isStoragePublic);

		$fileMock = $this->createMock(File::class);
		$fileMock->method('getStorage')->willReturn($storageMock);
		$fileMock->method('exists')->willReturn($fileExists);
		$fileMock->method('getPublicUrl')->willReturn($publicUrl);

		$subject = new MediaProcessingGuard(
			$this->providerMock,
			$this->extensionConfigurationMock
		);

		self::assertSame($expectedResult, $subject->isProcessingAllowed($fileMock));
	}

	public static function isProcessingAllowedDataProvider(): array
	{
		$defaultConfig = [
			'common' => [
				'backend' => true,
				'frontend' => true,
				'private' => true,
				'ignoreExtensionAssets' => false,
			],
		];

		return [
			'allowed: standard backend request with valid file' => [
				'config' => $defaultConfig,
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => true,
			],
			'allowed: standard frontend request with valid file' => [
				'config' => $defaultConfig,
				'applicationType' => 'frontend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => true,
			],
			'denied: disabled for backend in configuration' => [
				'config' => ['common' => ['backend' => false, 'frontend' => true, 'private' => true]],
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => false,
			],
			'denied: disabled for frontend in configuration' => [
				'config' => ['common' => ['backend' => true, 'frontend' => false, 'private' => true]],
				'applicationType' => 'frontend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => false,
			],
			'denied: storage is offline' => [
				'config' => $defaultConfig,
				'applicationType' => 'backend',
				'isStorageOnline' => false,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => false,
			],
			'denied: non-public storage with private processing disabled' => [
				'config' => ['common' => ['backend' => true, 'frontend' => true, 'private' => false]],
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => false,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => false,
			],
			'denied: file does not exist' => [
				'config' => $defaultConfig,
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => false,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => false,
			],
			'denied: provider has no configuration' => [
				'config' => $defaultConfig,
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => false,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => false,
			],
			'denied: ignoreExtensionAssets enabled and file in /_assets/' => [
				'config' => ['common' => ['backend' => true, 'frontend' => true, 'private' => true, 'ignoreExtensionAssets' => '1']],
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/_assets/1234/my_file.pdf',
				'expectedResult' => false,
			],
			'allowed: ignoreExtensionAssets enabled but file not in /_assets/' => [
				'config' => ['common' => ['backend' => true, 'frontend' => true, 'private' => true, 'ignoreExtensionAssets' => '1']],
				'applicationType' => 'backend',
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/my_file.pdf',
				'expectedResult' => true,
			],
			'allowed: CLI request (no TYPO3_REQUEST set)' => [
				'config' => $defaultConfig,
				'applicationType' => null,
				'isStorageOnline' => true,
				'isStoragePublic' => true,
				'fileExists' => true,
				'providerHasConfig' => true,
				'publicUrl' => '/fileadmin/doc.pdf',
				'expectedResult' => true,
			],
		];
	}
}
