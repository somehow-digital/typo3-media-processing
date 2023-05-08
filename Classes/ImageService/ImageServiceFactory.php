<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudflareUriBuilder;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudflareUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImageKitUriBuilder;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImageKitUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImagorFileSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImagorUriBuilder;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImagorUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyFileSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyUriBuilder;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\OptimoleUriBuilder;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\OptimoleUriSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ThumborFileSource;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ThumborUriBuilder;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ThumborUriSource;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageServiceFactory
{
	private array $configuration;

	public function __construct(ExtensionConfiguration $configuration)
	{
		$this->configuration = $configuration->get('media_processing');
	}

	public function __invoke(): ?ImageServiceInterface
	{
		$options = $this->configuration['integration'][$this->configuration['common']['integration']] ?? [];

		return match ($this->configuration['common']['integration']) {
			default => null,
			ImgProxyImageService::getIdentifier() => $this->getImgProxyImageService($options),
			ImagorImageService::getIdentifier() => $this->getImagorImageService($options),
			ThumborImageService::getIdentifier() => $this->getThumborImageService($options),
			OptimoleImageService::getIdentifier() => $this->getOptimoleImageService($options),
			CloudflareImageService::getIdentifier() => $this->getCloudflareImageService($options),
			ImageKitImageService::getIdentifier() => $this->getImageKitImageService($options),
		};
	}

	private function getImgProxyImageService(array $options): ImgProxyImageService
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

		$builder = new ImgProxyUriBuilder(
			$options['api_endpoint'],
			$source,
			$options['signature'] ? $options['signature_key'] : null,
			$options['signature'] ? $options['signature_salt'] : null,
			$options['signature'] ? (int) $options['signature_size'] : 0,
			$options['encryption'] ? $options['encryption_key'] : null,
		);

		return new ImgProxyImageService(
			$options['api_endpoint'],
			$builder,
		);
	}

	private function getImagorImageService(array $options): ImagorImageService
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

		$builder = new ImagorUriBuilder(
			$options['api_endpoint'],
			$source,
			$options['signature'] ? $options['signature_key'] : null,
			$options['signature'] ? $options['signature_algorithm'] : null,
			$options['signature'] ? (int) $options['signature_length'] : 0,
		);

		return new ImagorImageService(
			$options['api_endpoint'],
			$builder,
		);
	}

	private function getThumborImageService(array $options): ThumborImageService
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

		$builder = new ThumborUriBuilder(
			$options['api_endpoint'],
			$source,
			$options['signature'] ? $options['signature_key'] : null,
			$options['signature'] ? $options['signature_algorithm'] : null,
			$options['signature'] ? (int) $options['signature_length'] : 0,
		);

		return new ThumborImageService(
			$options['api_endpoint'],
			$builder,
		);
	}

	private function getOptimoleImageService(array $options): OptimoleImageService
	{
		$source = new OptimoleUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		$builder = new OptimoleUriBuilder(
			$options['api_key'],
			$source,
		);

		return new OptimoleImageService(
			$options['api_key'],
			$builder,
		);
	}

	private function getCloudflareImageService(array $options): CloudflareImageService
	{
		$source = new CloudflareUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		$builder = new CloudflareUriBuilder(
			$options['api_endpoint'],
			$source,
		);

		return new CloudflareImageService(
			$options['api_endpoint'],
			$builder,
		);
	}

	private function getImageKitImageService(array $options): ImageKitImageService
	{
		$source = new ImageKitUriSource(
			$options['source_uri'] ?: GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'),
		);

		$builder = new ImageKitUriBuilder(
			$options['api_endpoint'],
			$source,
		);

		return new ImageKitImageService(
			$options['api_endpoint'],
			$builder,
		);
	}
}
