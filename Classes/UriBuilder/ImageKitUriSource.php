<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use SomehowDigital\Typo3\MediaProcessing\Utility\OnlineMediaUtility;
use TYPO3\CMS\Core\Resource\FileInterface;

class ImageKitUriSource implements UriSourceInterface
{
	public const IDENTIFIER = 'uri';

	public function __construct(
		private readonly string $host,
	) {
	}

	public function getSource(FileInterface $file): string
	{
		$url = OnlineMediaUtility::getPreviewImage($file) ?? $file->getPublicUrl();

		return $this->build($url);
	}

	private function build(string $url): string
	{
		$path = parse_url($url, PHP_URL_PATH);
		$query = parse_url($url, PHP_URL_QUERY) ?? '';

		return strtr('%host%/%path%', [
			'%host%' => trim($this->host, '/'),
			'%path%' => implode('?', array_filter([trim($path, '/'), trim($query)])),
		]);
	}
}
