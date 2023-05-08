<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class ThumborUriBuilder extends UriBuilderAbstract
{
	public function __construct(
		private readonly string $endpoint,
		private readonly UriSourceInterface $source,
		private readonly ?string $key,
		private readonly ?string $algorithm,
		private readonly ?int $length,
	) {
	}

	public function createFromFile(FileInterface $file, array $configuration): ThumborUri
	{
		$uri = new ThumborUri(
			$this->endpoint,
			$this->key,
			$this->algorithm,
			$this->length,
		);

		$uri->setSource($this->source->getSource(($file)));

		$type = (static function ($configuration) {
			switch (true) {
				default:
					return 'stretch';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'fit-in';
			}
		})($configuration);

		$type && $uri->setType($type);

		if (isset($configuration['crop'])) {
			$uri->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
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
