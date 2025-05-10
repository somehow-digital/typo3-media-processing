<?php

namespace SomehowDigital\Typo3\MediaProcessing\Event;

use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderResultInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class MediaProcessedEvent
{
	public function __construct(
		private readonly ProviderInterface $provider,
		private readonly TaskInterface $task,
		private readonly ProviderResultInterface $result,
	) {}

	public function getProvider(): ProviderInterface
	{
		return $this->provider;
	}

	public function getTask(): TaskInterface
	{
		return $this->task;
	}

	public function getResult(): ProviderResultInterface
	{
		return $this->result;
	}
}
