<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\BunnyUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudflareUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudImageUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudinaryFetchSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudinaryUploadSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\GumletFolderSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\GumletProxySource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImageKitUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImagorFileSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImagorUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgixFolderSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgixProxySource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyFileSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\OptimoleUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\SirvUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ThumborFileSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ThumborUriSource;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProviderFactory
{
	private array $configuration;

	public function __construct(ExtensionConfiguration $configuration)
	{
		$this->configuration = $configuration->get('media_processing');
	}

	public function __invoke(): ?ProviderInterface
	{
		$options = $this->configuration['provider'][$this->configuration['common']['provider']] ?? [];

		return match ($this->configuration['common']['provider']) {
			default => null,
			ImgProxyProvider::getIdentifier() => $this->getImgProxyProvider($options),
			ImagorProvider::getIdentifier() => $this->getImagorProvider($options),
			ThumborProvider::getIdentifier() => $this->getThumborProvider($options),
			OptimoleProvider::getIdentifier() => $this->getOptimoleProvider($options),
			BunnyProvider::getIdentifier() => $this->getBunnyProvider($options),
			CloudflareProvider::getIdentifier() => $this->getCloudflareProvider($options),
			ImageKitProvider::getIdentifier() => $this->getImageKitProvider($options),
			SirvProvider::getIdentifier() => $this->getSirvProvider($options),
			ImgixProvider::getIdentifier() => $this->getImgixProvider($options),
			CloudinaryProvider::getIdentifier() => $this->getCloudinaryProvider($options),
			CloudImageProvider::getIdentifier() => $this->getCloudImageProvider($options),
			GumletProvider::getIdentifier() => $this->getGumletProvider($options),
		};
	}

	private function getImgProxyProvider(array $options): ImgProxyProvider
	{
		$source = match ($options['source_loader']) {
			ImgProxyUriSource::IDENTIFIER => (static function () use ($options): ImgProxyUriSource {
				return new ImgProxyUriSource(
					$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
				);
			})(),
			ImgProxyFileSource::IDENTIFIER => (static function (): ImgProxyFileSource {
				return new ImgProxyFileSource();
			})(),
		};

		return new ImgProxyProvider(
			$source,
			$options,
		);
	}

	private function getImagorProvider(array $options): ImagorProvider
	{
		$source = match ($options['source_loader']) {
			ImagorUriSource::IDENTIFIER => (static function () use ($options): ImagorUriSource {
				return new ImagorUriSource(
					$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
				);
			})(),
			ImagorFileSource::IDENTIFIER => (static function (): ImagorFileSource {
				return new ImagorFileSource();
			})(),
		};

		return new ImagorProvider(
			$source,
			$options,
		);
	}

	private function getThumborProvider(array $options): ThumborProvider
	{
		$source = match ($options['source_loader']) {
			ThumborUriSource::IDENTIFIER => (static function () use ($options): ThumborUriSource {
				return new ThumborUriSource(
					$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
				);
			})(),
			ThumborFileSource::IDENTIFIER => (static function (): ThumborFileSource {
				return new ThumborFileSource();
			})(),
		};

		return new ThumborProvider(
			$source,
			$options,
		);
	}

	private function getOptimoleProvider(array $options): OptimoleProvider
	{
		$source = new OptimoleUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		return new OptimoleProvider(
			$source,
			$options,
		);
	}

	private function getBunnyProvider(array $options): BunnyProvider
	{
		$source = new BunnyUriSource();

		return new BunnyProvider(
			$source,
			$options,
		);
	}

	private function getCloudflareProvider(array $options): CloudflareProvider
	{
		$source = new CloudflareUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		return new CloudflareProvider(
			$source,
			$options,
		);
	}

	private function getImageKitProvider(array $options): ImageKitProvider
	{
		$source = new ImageKitUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		return new ImageKitProvider(
			$source,
			$options,
		);
	}

	private function getSirvProvider(array $options): SirvProvider
	{
		$source = new SirvUriSource();

		return new SirvProvider(
			$source,
			$options,
		);
	}

	private function getImgixProvider(array $options): ImgixProvider
	{
		$source = match ($options['source_loader']) {
			ImgixProxySource::IDENTIFIER => (static function () use ($options): ImgixProxySource {
				return new ImgixProxySource(
					$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
				);
			})(),
			ImgixFolderSource::IDENTIFIER => (static function (): ImgixFolderSource {
				return new ImgixFolderSource();
			})(),
		};

		return new ImgixProvider(
			$source,
			$options,
		);
	}

	private function getCloudinaryProvider(array $options): CloudinaryProvider
	{
		$source = match ($options['delivery_mode']) {
			CloudinaryFetchSource::IDENTIFIER => (static function () use ($options): CloudinaryFetchSource {
				return new CloudinaryFetchSource(
					$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
				);
			})(),
			CloudinaryUploadSource::IDENTIFIER => (static function (): CloudinaryUploadSource {
				return new CloudinaryUploadSource();
			})(),
		};

		return new CloudinaryProvider(
			$source,
			$options,
		);
	}

	private function getCloudImageProvider(array $options): CloudImageProvider
	{
		$source = new CloudImageUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		return new CloudImageProvider(
			$source,
			$options,
		);
	}

	private function getGumletProvider(array $options): GumletProvider
	{
		$source = match ($options['source_loader']) {
			GumletFolderSource::IDENTIFIER => (static function (): GumletFolderSource {
				return new GumletFolderSource();
			})(),
			GumletProxySource::IDENTIFIER => (static function () use ($options): GumletProxySource {
				return new GumletProxySource(
					$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
				);
			})(),
		};

		return new GumletProvider(
			$source,
			$options,
		);
	}
}
