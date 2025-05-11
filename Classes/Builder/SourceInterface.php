<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

use TYPO3\CMS\Core\Resource\FileInterface;

interface SourceInterface
{
	public function getSource(FileInterface $file): string;
}
