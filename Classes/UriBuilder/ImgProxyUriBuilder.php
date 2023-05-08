<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class ImgProxyUriBuilder extends UriBuilderAbstract
{
	public function __construct(
		private readonly string $endpoint,
		private readonly UriSourceInterface $source,
		private readonly ?string $key,
		private readonly ?string $salt,
		private readonly ?int $size,
		private readonly ?string $secret,
	) {
	}

	public function createFromFile(FileInterface $file, array $configuration): ImgProxyUri
	{
		$uri = new ImgProxyUri(
			$this->endpoint,
			$this->key,
			$this->salt,
			$this->size,
			$this->secret,
		);

		$uri->setSource($this->source->getSource(($file)));

		$type = (static function ($configuration) {
			switch (true) {
				default:
					return 'force';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'fit';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'fill';
			}
		})($configuration);

		$uri->setType($type);

		if (isset($configuration['crop'])) {
			$uri->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				[
					ImgProxyUri::GRAVITY_TOP_LEFT,
					(int) $configuration['crop']->getOffsetLeft(),
					(int) $configuration['crop']->getOffsetTop(),
				],
			);
		}

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$uri->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['minWidth'])) {
			$uri->setMinWidth((int) $configuration['minWidth']);
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$uri->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['minHeight'])) {
			$uri->setMinHeight((int) $configuration['minHeight']);
		}

		$uri->setHash($file->getSha1());

		return $uri;
	}
}
