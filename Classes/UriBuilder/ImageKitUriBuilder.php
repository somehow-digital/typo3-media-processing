<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class ImageKitUriBuilder extends UriBuilderAbstract
{
	public function __construct(
		private readonly string $endpoint,
		private readonly UriSourceInterface $source,
	) {
	}

	public function createFromFile(FileInterface $file, array $configuration): ImageKitUri
	{
		$uri = new ImageKitUri($this->endpoint);
		$uri->setSource($this->source->getSource($file));

		$crop = (static function ($configuration) {
			switch (true) {
				default:
					return 'force';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'at_max';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'maintain_ratio';
			}
		})($configuration);

		$uri->setCrop($crop);

		if (isset($configuration['crop'])) {
			$uri->setOffset(
				(int) $configuration['crop']->getOffsetLeft(),
				(int) $configuration['crop']->getOffsetTop(),
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
