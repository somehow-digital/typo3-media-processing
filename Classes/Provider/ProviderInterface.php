<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\Builder\BuilderInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

interface ProviderInterface
{
	public function getEndpoint(): string;

	public function hasConfiguration(): bool;

	public function supports(TaskInterface $task): bool;

	public function configure(TaskInterface $task): BuilderInterface;
}
