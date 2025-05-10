<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

interface ProviderInterface
{
	public function configureOptions(OptionsResolver $resolver): void;

	public function getEndpoint(): string;

	public function hasConfiguration(): bool;

	public function supports(TaskInterface $task): bool;

	public function process(TaskInterface $task): ProviderResultInterface;
}
