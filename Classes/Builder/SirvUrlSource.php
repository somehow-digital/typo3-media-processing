<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

use TYPO3\CMS\Core\Resource\FileInterface;

class SirvUrlSource implements SourceInterface
{
	public function getSource(FileInterface $file): string
	{
		return $this->build($file);
	}

	private function build(FileInterface $source): string
	{
		$path = parse_url($source->getPublicUrl(), PHP_URL_PATH);
		$query = parse_url($source->getPublicUrl(), PHP_URL_QUERY) ?? '';

		return implode('?', array_filter([trim($path, '/'), trim($query)]));
	}
}
