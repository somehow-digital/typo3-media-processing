<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Processor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use SomehowDigital\Typo3\MediaProcessing\Builder\BuilderInterface;
use SomehowDigital\Typo3\MediaProcessing\Event\MediaProcessedEvent;
use SomehowDigital\Typo3\MediaProcessing\Processor\MediaProcessor;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MediaProcessorTest extends UnitTestCase
{
	private ProviderInterface&MockObject $providerMock;
	private EventDispatcherInterface&MockObject $dispatcherMock;
	private ExtensionConfiguration&MockObject $extensionConfigurationMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->providerMock = $this->createMock(ProviderInterface::class);
		$this->dispatcherMock = $this->createMock(EventDispatcherInterface::class);
		$this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['TYPO3_REQUEST']);
		parent::tearDown();
	}

	#[Test]
	public function canProcessTaskReturnsTrueWhenAllConditionsAreMet(): void
	{
		$GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
			->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

		$storageMock = $this->createMock(ResourceStorage::class);

		$storageMock
			->expects($this->once())
			->method('isOnline')
			->willReturn(true);

		$storageMock
			->expects($this->once())
			->method('isPublic')
			->willReturn(false);
		$sourceFileMock = $this->createMock(File::class);

		$sourceFileMock
			->expects($this->once())
			->method('exists')
			->willReturn(true);

		$sourceFileMock
			->expects($this->atLeastOnce())
			->method('getProperty')
			->willReturn(1920);

		$sourceFileMock
			->expects($this->atLeastOnce())
			->method('getStorage')
			->willReturn($storageMock);

		$taskMock = $this->createMock(TaskInterface::class);

		$taskMock
			->expects($this->atLeastOnce())
			->method('getSourceFile')
			->willReturn($sourceFileMock);

		$this->extensionConfigurationMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn([
				'common' => [
					'backend' => true,
					'private' => true,
				],
			]);

		$this->providerMock
			->expects($this->once())
			->method('supports')
			->with($taskMock)
			->willReturn(true);

		$this->providerMock
			->expects($this->once())
			->method('hasConfiguration')
			->willReturn(true);

		$this->dispatcherMock
			->expects($this->never())
			->method('dispatch');

		$processor = new MediaProcessor(
			$this->providerMock,
			$this->dispatcherMock,
			$this->extensionConfigurationMock
		);

		$this->assertTrue($processor->canProcessTask($taskMock));
	}

	#[Test]
	public function canProcessTaskReturnsFalseIfBackendNotAllowedByConfig(): void
	{
		$GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
			->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

		$this->dispatcherMock
			->expects($this->never())
			->method('dispatch');

		$this->providerMock
			->expects($this->never())
			->method('hasConfiguration')
			->willReturn(true);

		$this->extensionConfigurationMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn([
				'common' => ['frontend' => false],
			]);

		$processor = new MediaProcessor(
			$this->providerMock,
			$this->dispatcherMock,
			$this->extensionConfigurationMock
		);

		$this->assertFalse($processor->canProcessTask($this->createStub(TaskInterface::class)));
	}

	#[Test]
	public function processTaskReturnsEarlyIfChecksumMatches(): void
	{
		$targetFileMock = $this->createMock(ProcessedFile::class);

		$targetFileMock
			->expects($this->once())
			->method('getProperty')
			->with('checksum')
			->willReturn('matched-checksum');

		$targetFileMock
			->expects($this->never())
			->method('setName');

		$taskMock = $this->createMock(TaskInterface::class);

		$taskMock
			->expects($this->once())
			->method('getConfigurationChecksum')
			->willReturn('matched-checksum');

		$taskMock
			->expects($this->once())
			->method('getTargetFile')
			->willReturn($targetFileMock);

		$taskMock
			->expects($this->once())
			->method('setExecuted')
			->with(true);

		$this->extensionConfigurationMock
			->expects($this->once())
			->method('get');

		$this->providerMock
			->expects($this->never())
			->method('supports')
			->with($taskMock)
			->willReturn(true);

		$this->dispatcherMock
			->expects($this->never())
			->method('dispatch');

		$processor = new MediaProcessor(
			$this->providerMock,
			$this->dispatcherMock,
			$this->extensionConfigurationMock
		);

		$processor->processTask($taskMock);
	}

	#[Test]
	public function processTaskExecutesAndDispatchesEvent(): void
	{
		$targetFileMock = $this->createMock(ProcessedFile::class);

		$targetFileMock
			->expects($this->once())
			->method('getProperty')
			->with('checksum')
			->willReturn('other-checksum');

		$targetFileMock
			->expects($this->once())
			->method('setName')
			->with('processed_image.jpg');

		$targetFileMock
			->expects($this->once())
			->method('updateProperties')
			->with([
				'width' => 800,
				'height' => 600,
				'checksum' => 'new-checksum',
				'processing_url' => 'https://example.com/media/image.jpg',
			]);

		$taskMock = $this->createMock(TaskInterface::class);

		$taskMock
			->expects($this->atLeastOnce())
			->method('getConfigurationChecksum')
			->willReturn('new-checksum');

		$taskMock
			->expects($this->atLeastOnce())
			->method('getTargetFile')
			->willReturn($targetFileMock);

		$taskMock
			->expects($this->atLeastOnce())
			->method('getTargetFileName')
			->willReturn('processed_image.jpg');

		$taskMock
			->expects($this->once())
			->method('getConfiguration')->willReturn([
				'crop' => new Area(0, 0, 800, 600),
			]);

		$builderMock = $this->createMock(BuilderInterface::class);

		$builderMock->expects($this->once())
			->method('build')
			->willReturn('https://example.com/media/image.jpg');

		$this->providerMock
			->expects($this->once())
			->method('configure')
			->with($taskMock)
			->willReturn($builderMock);

		$this->dispatcherMock
			->expects($this->once())
			->method('dispatch')
			->with($this->isInstanceOf(MediaProcessedEvent::class))
			->willReturnArgument(0);

		$taskMock
			->expects($this->once())
			->method('setExecuted')
			->with(true);

		$this->extensionConfigurationMock
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn([
				'common' => ['storage' => false],
			]);

		$processor = new MediaProcessor(
			$this->providerMock,
			$this->dispatcherMock,
			$this->extensionConfigurationMock
		);

		$processor->processTask($taskMock);
	}
}
