<?php

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;

interface ImageServiceResultInterface
{
	public function getUri(): UriInterface;
	public function getDimension(): ImageDimension;
}
