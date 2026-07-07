<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\BuilderInterface;
use SomehowDigital\Typo3\MediaProcessing\Event\MediaProcessedEvent;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MediaProcessedEventTest extends UnitTestCase
{
	#[Test]
	public function constructorSetsInitialPropertiesCorrectly(): void
	{
		$taskStub = $this->createStub(TaskInterface::class);
		$builderStub = $this->createStub(BuilderInterface::class);

		$event = new MediaProcessedEvent($taskStub, $builderStub);

		$this->assertSame($taskStub, $event->getTask());
		$this->assertSame($builderStub, $event->getBuilder());
	}

	#[Test]
	public function setBuilderOverwritesExistingBuilderInstance(): void
	{
		$taskStub = $this->createStub(TaskInterface::class);
		$initialBuilderStub = $this->createStub(BuilderInterface::class);
		$newBuilderStub = $this->createStub(BuilderInterface::class);

		$event = new MediaProcessedEvent($taskStub, $initialBuilderStub);

		$event->setBuilder($newBuilderStub);

		$this->assertSame($newBuilderStub, $event->getBuilder());
		$this->assertNotSame($initialBuilderStub, $event->getBuilder());
	}
}
