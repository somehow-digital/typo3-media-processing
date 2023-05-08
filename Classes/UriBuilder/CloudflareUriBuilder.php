<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class CloudflareUriBuilder extends UriBuilderAbstract
{
	public function __construct(
		private readonly string $endpoint,
		private readonly UriSourceInterface $source,
	) {
	}

	public function createFromFile(FileInterface $file, array $configuration): CloudflareUri
	{
		$uri = new CloudflareUri($this->endpoint);
		$uri->setSource($this->source->getSource($file));

		$fit = (static function ($configuration) {
			switch (true) {
				default:
					return 'contain';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'cover';
			}
		})($configuration);

		$uri->setFit($fit);

		if (isset($configuration['crop'])) {
			$uri->setTrim(
				(int) $configuration['crop']->getOffsetTop(),
				(int) ($file->getProperty('width') - $configuration['crop']->getWidth() - $configuration['crop']->getOffsetLeft()),
				(int) ($file->getProperty('height') - $configuration['crop']->getHeight() - $configuration['crop']->getOffsetTop()),
				(int) $configuration['crop']->getOffsetLeft(),
			);
		}

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$uri->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$uri->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		return $uri;
	}
}
