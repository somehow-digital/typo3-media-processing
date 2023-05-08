<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class ImgProxyFileSource implements UriSourceInterface
{
	public const IDENTIFIER = 'file';

	public const PROTOCOL = 'local://';

	public function getSource(FileInterface $file): string
	{
		return $this->build($file);
	}

	private function build(FileInterface $source): string
	{
		return strtr('%protocol%/%path%', [
			'%protocol%' => static::PROTOCOL,
			'%path%' => trim($source->getPublicUrl(), '/'),
		]);
	}
}
