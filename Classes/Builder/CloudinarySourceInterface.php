<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

interface CloudinarySourceInterface extends SourceInterface
{
	public function getIdentifier(): string;
}
