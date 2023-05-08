<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

interface UriSourceInterface
{
	public function getSource(FileInterface $file): string;
}
