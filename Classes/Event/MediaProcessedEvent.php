<?php

namespace SomehowDigital\Typo3\MediaProcessing\Event;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceResultInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class MediaProcessedEvent
{
	private ImageServiceInterface $service;
	private ImageServiceResultInterface $result;
	private TaskInterface $task;

	public function __construct(
		ImageServiceInterface $service,
		TaskInterface $task,
		ImageServiceResultInterface $result,
	) {
		$this->service = $service;
		$this->task = $task;
		$this->result = $result;
	}

	public function getService(): ImageServiceInterface
	{
		return $this->service;
	}

	public function getTask(): TaskInterface
	{
		return $this->task;
	}

	public function getResult(): ImageServiceResultInterface
	{
		return $this->result;
	}
}
