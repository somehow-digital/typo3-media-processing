<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

interface UriInterface
{
	public function __invoke(): string;

	public function __toString(): string;
}
