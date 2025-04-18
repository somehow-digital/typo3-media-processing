<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use SomehowDigital\Typo3\MediaProcessing\Utility\OnlineMediaUtility;
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
		$url = OnlineMediaUtility::getPreviewImage($file) ?? $file->getIdentifier();

		return $this->build($url);
	}

	private function build(string $url): string
	{
		$path = parse_url($url, PHP_URL_PATH);
		$query = parse_url($url, PHP_URL_QUERY) ?? '';

		return implode('?', array_filter([trim($path, '/'), trim($query)]));
	}
}
