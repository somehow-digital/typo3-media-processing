<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

interface UriBuilderInterface
{
	public function createFromFile(FileInterface $file, array $configuration): UriInterface;
}
