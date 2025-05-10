<?php

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;

interface ProviderResultInterface
{
	public function getUri(): UriInterface;
	public function getDimension(): ImageDimension;
}
