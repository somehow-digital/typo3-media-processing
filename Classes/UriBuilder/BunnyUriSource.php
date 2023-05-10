<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

class BunnyUriSource implements UriSourceInterface
{
	public function getSource(FileInterface $file): string
	{
		return $this->build($file);
	}

	private function build(FileInterface $source): string
	{
		$base = PathUtility::dirname(
			Environment::getPublicPath() . '/' . $source->getPublicUrl(),
		);

		$path = PathUtility::getRelativePath(
			Environment::getPublicPath(),
			$base,
		);

		$file = mb_substr(
			Environment::getPublicPath() . '/' . $source->getPublicUrl(),
			strlen($base) + 1,
		);

		return strtr('%path%/%file%', [
			'%path%' => trim($path, '/'),
			'%file%' => trim($file, '/'),
		]);
	}
}
