<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

use TYPO3\CMS\Core\Resource\FileInterface;

class CloudinaryFetchSource implements UriSourceInterface
{
	public const IDENTIFIER = 'fetch';

	public function __construct(
		private readonly string $host,
	) {
	}

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
		$path = parse_url($source->getPublicUrl(), PHP_URL_PATH);

		return strtr('%host%/%path%', [
			'%host%' => trim($this->host, '/'),
			'%path%' => trim($path, '/'),
		]);
	}
}
