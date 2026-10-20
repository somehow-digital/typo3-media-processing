<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use SomehowDigital\Typo3\MediaProcessing\EventListener\DocumentDimensionsEventListener;
use SomehowDigital\Typo3\MediaProcessing\Service\MediaProcessingGuard;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DocumentDimensionsEventListenerTest extends UnitTestCase
{
	private Parser&MockObject $parserMock;
	private MediaProcessingGuard&MockObject $guardMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->guardMock = $this->createMock(MediaProcessingGuard::class);
		$this->parserMock = $this->createMock(Parser::class);

		$GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
			->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
	}

	#[Test]
	public function doesNothingWhenWidthAlreadyExists(): void
	{
		$event = $this->createEvent(
			width: 100
		);

		$this->guardMock
			->expects($this->once())
			->method('isProcessingAllowed')
			->willReturn(true);

		$this->parserMock
			->expects($this->never())
			->method('parseFile');

		$listener = new DocumentDimensionsEventListener(
			$this->guardMock,
			$this->parserMock,
		);

		$listener($event);
	}



	#[Test]
	public function doesNothingWhenGuardDisallowsProcessing(): void
	{
		$event = $this->createEvent(taskType: 'UnsupportedTask');

		$this->guardMock
			->expects($this->once())
			->method('isProcessingAllowed')
			->willReturn(false);

		$this->parserMock
			->expects($this->never())
			->method('parseFile');

		$listener = new DocumentDimensionsEventListener(
			$this->guardMock,
			$this->parserMock,
		);

		$listener($event);
	}

	#[Test]
	public function readsDimensionsFromPdf(): void
	{
		$this->guardMock
			->expects($this->once())
			->method('isProcessingAllowed')
			->willReturn(true);

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
			$this->guardMock,
			$this->parserMock,
		);

		$listener($event);
	}

	private function createEvent(
		?int $width = null,
		?int $height = null,
		?MetaDataAspect $metadata = null,
		string $taskType = 'Preview'
	): BeforeFileProcessingEvent {

		$fileStub = $this->createStub(File::class);
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
