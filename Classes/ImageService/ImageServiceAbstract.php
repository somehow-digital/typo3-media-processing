<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriInterface;
use TYPO3\CMS\Core\Resource\FileInterface;

abstract class ImageServiceAbstract implements ImageServiceInterface
{
	public function buildUrl(
		FileInterface $file,
		array $configuration,
	): UriInterface {
		return $this->builder->createFromFile($file, $configuration);
	}
}
