<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

interface ImageServiceInterface
{
	public function configureOptions(OptionsResolver $resolver): void;

	public function getEndpoint(): string;

	public function hasConfiguration(): bool;

	public function canProcessTask(TaskInterface $task): bool;

	public function processTask(TaskInterface $task): ImageServiceResult;
}
