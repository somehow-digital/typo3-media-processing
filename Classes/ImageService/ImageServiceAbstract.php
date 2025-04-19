<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use TYPO3\CMS\Core\Resource\FileInterface;

abstract class ImageServiceAbstract implements ImageServiceInterface
{
	protected array $options = [];

	public function calculateChecksum(FileInterface $file): string
	{
		return sha1(
			$file->getIdentifier() .
			$file->getSize() .
			serialize($this->options)
		);
	}
}
