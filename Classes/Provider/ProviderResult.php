<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;

class ProviderResult implements ProviderResultInterface
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
