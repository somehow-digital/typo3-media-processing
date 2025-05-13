<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

use SomehowDigital\Typo3\MediaProcessing\Utility\OnlineMediaUtility;
use TYPO3\CMS\Core\Resource\FileInterface;

class ImgLabWebSource implements SourceInterface
{
	public const IDENTIFIER = 'web';

	public function getSource(FileInterface $file): string
	{
		$url = OnlineMediaUtility::getPreviewImage($file) ?? $file->getPublicUrl();

		return $this->build($url);
	}

	private function build(string $url): string
	{
		$path = parse_url($url, PHP_URL_PATH);
		$query = parse_url($url, PHP_URL_QUERY) ?? '';

		return implode('?', array_filter([trim($path, '/'), trim($query)]));
	}
}
