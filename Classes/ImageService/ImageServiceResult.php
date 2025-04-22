<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;

class ImageServiceResult implements ImageServiceResultInterface
{
	public function __construct(
		private readonly UriInterface $uri,
		private readonly ImageDimension $dimension,
	) {
	}

	public function getUri(): UriInterface
	{
		return $this->uri;
	}

	public function getDimension(): ImageDimension
	{
		return $this->dimension;
	}
}
