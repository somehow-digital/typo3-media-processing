<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

use SomehowDigital\ImgProxy\Url;

class ImgProxyBuilder implements BuilderInterface
{
	public function __construct(
		private readonly string $endpoint,
		private readonly Url $url,
	) {}

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function getUrl(): Url
	{
		return $this->url;
	}

	public function build(): string
	{
		return rtrim($this->endpoint, '/') . '/' . $this->url->build();
	}
}
