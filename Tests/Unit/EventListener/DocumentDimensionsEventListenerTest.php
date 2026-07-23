<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use SomehowDigital\Typo3\MediaProcessing\EventListener\DocumentDimensionsEventListener;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DocumentDimensionsEventListenerTest extends UnitTestCase
{
	private ProviderInterface&MockObject $providerMock;
	private Parser&MockObject $parserMock;
	private ExtensionConfiguration&MockObject $extensionConfiguration;

	protected function setUp(): void
	{
		parent::setUp();

		$this->providerMock = $this->createMock(ProviderInterface::class);
		$this->parserMock = $this->createMock(Parser::class);
		$this->extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
		$this->resetSingletonInstances = true;

		$this->extensionConfiguration
			->expects($this->once())
			->method('get')
			->with('media_processing')
			->willReturn([
				'common' => [
					'backend' => true,
					'frontend' => true,
					'private' => true,
				],
			]);

		$GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
			->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
	}

	#[Test]
	public function doesNothingWhenWidthAlreadyExists(): void
	{
		$event = $this->createEvent(
			width: 100
		);

		$this->providerMock
			->expects($this->never())
			->method('supports');

		$this->parserMock
			->expects($this->never())
			->method('parseFile');

		$listener = new DocumentDimensionsEventListener(
			$this->providerMock,
			$this->parserMock,
			$this->extensionConfiguration
		);

		$listener($event);
	}

	#[Test]
	public function doesNothingWhenProviderDoesNotSupportTask(): void
	{
		$event = $this->createEvent(taskType: 'UnsupportedTask');

		$this->providerMock
			->expects($this->once())
			->method('hasConfiguration')
			->willReturn(true);

		$this->providerMock
			->expects($this->never())
			->method('supports');

		$this->parserMock
			->expects($this->never())
			->method('parseFile');

		$listener = new DocumentDimensionsEventListener(
			$this->providerMock,
			$this->parserMock,
			$this->extensionConfiguration
		);

		$listener($event);
	}

	#[Test]
	public function readsDimensionsFromPdf(): void
	{
		$metadataMock = $this->createMock(MetaDataAspect::class);

		$metadataMock
			->expects($this->once())
			->method('add')
			->with([
				'width' => 595,
				'height' => 842,
			]);

		$event = $this->createEvent(
			metadata: $metadataMock
		);

		$this->providerMock
			->expects($this->once())
			->method('hasConfiguration')
			->willReturn(true);

		$this->providerMock
			->expects($this->never())
			->method('supports');

		$pageMock = $this->createMock(Page::class);

		$pageMock
			->expects($this->once())
			->method('getDetails')
			->willReturn([
				'MediaBox' => [0, 0, 595, 842],
			]);

		$document = $this->createMock(Document::class);

		$document
			->expects($this->once())
			->method('getPages')
			->willReturn([$pageMock]);

		$this->parserMock
			->expects($this->once())
			->method('parseFile')
			->with('/tmp/document.pdf')
			->willReturn($document);

		$listener = new DocumentDimensionsEventListener(
			$this->providerMock,
			$this->parserMock,
			$this->extensionConfiguration
		);

		$listener($event);
	}

	private function createEvent(
		?int $width = null,
		?int $height = null,
		?MetaDataAspect $metadata = null,
		string $taskType = 'Preview'
	): BeforeFileProcessingEvent {
		$storageMock = $this->createMock(ResourceStorage::class);

		$storageMock
			->expects($this->atLeastOnce())
			->method('isOnline')
			->willReturn(true);

		$storageMock
			->expects($this->atLeastOnce())
			->method('isPublic')
			->willReturn(true);

		$fileStub = $this->createStub(File::class);

		$fileStub->method('getStorage')->willReturn($storageMock);
		$fileStub->method('exists')->willReturn(true);
		$fileStub->method('getForLocalProcessing')->willReturn('/tmp/document.pdf');

		$fileStub->method('getProperty')
			->willReturnCallback(
				static function (string $property) use ($width, $height) {
					return match ($property) {
						'width' => $width,
						'height' => $height,
						default => null,
					};
				}
			);

		$fileStub->method('getMetaData')
			->willReturn($metadata ?? $this->createStub(MetaDataAspect::class));

		return new BeforeFileProcessingEvent(
			$this->createStub(DriverInterface::class),
			$this->createStub(ProcessedFile::class),
			$fileStub,
			$taskType,
			[]
		);
	}
}
