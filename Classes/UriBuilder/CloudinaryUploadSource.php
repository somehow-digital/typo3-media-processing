<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class CloudinaryUploadSource implements UriSourceInterface
{
	public const IDENTIFIER = 'upload';

	public function getIdentifier(): string
	{
		return self::IDENTIFIER;
	}

	public function getSource(FileInterface $file): string
	{
		return $this->build($file);
	}

	private function build(FileInterface $source): string
	{
		return parse_url($source->getPublicUrl(), PHP_URL_PATH);
	}
}
