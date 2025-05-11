<?php

namespace SomehowDigital\Typo3\MediaProcessing\Event;

use SomehowDigital\Typo3\MediaProcessing\Builder\BuilderInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class MediaProcessedEvent
{
	public function __construct(
		private readonly TaskInterface $task,
		private BuilderInterface $builder,
	) {}

	public function getTask(): TaskInterface
	{
		return $this->task;
	}

	public function getBuilder(): BuilderInterface
	{
		return $this->builder;
	}

	public function setBuilder(BuilderInterface $builder): void
	{
		$this->builder = $builder;
	}
}
