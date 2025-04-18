<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use SomehowDigital\Typo3\MediaProcessing\Utility\OnlineMediaUtility;
use TYPO3\CMS\Core\Resource\FileInterface;

class ImgProxyFileSource implements UriSourceInterface
{
	public const IDENTIFIER = 'file';

	public const PROTOCOL = 'local://';

	public function getSource(FileInterface $file): string
	{
		$url = OnlineMediaUtility::getPreviewImage($file) ?? $file->getIdentifier();

		return $this->build($url);
	}

	private function build(string $url): string
	{
		$path = parse_url($url, PHP_URL_PATH);
		$query = parse_url($url, PHP_URL_QUERY) ?? '';

		return strtr('%protocol%/%path%', [
			'%protocol%' => static::PROTOCOL,
			'%path%' => implode('?', array_filter([trim($path, '/'), trim($query)])),
		]);
	}
}
