<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

interface BuilderInterface
{
	public function build(): string;
}
