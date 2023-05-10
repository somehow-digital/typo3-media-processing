<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class BunnyUriBuilder extends UriBuilderAbstract
{
	public function __construct(
		private readonly string $endpoint,
		private readonly UriSourceInterface $source,
	) {
	}

	public function createFromFile(FileInterface $file, array $configuration): BunnyUri
	{
		$uri = new BunnyUri($this->endpoint);

		$uri->setSource($this->source->getSource($file));

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
