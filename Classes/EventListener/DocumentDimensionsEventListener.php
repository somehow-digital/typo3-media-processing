<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use Smalot\PdfParser\Parser;
use SomehowDigital\Typo3\MediaProcessing\Service\MediaProcessingGuard;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;

class DocumentDimensionsEventListener
{
	public function __construct(
		private readonly MediaProcessingGuard $guard,
		private readonly Parser $parser,
	) {}

	public function __invoke(BeforeFileProcessingEvent $event): void
	{
		$file = $event->getFile();

		if (!$this->guard->isProcessingAllowed($file)) {
			return;
		}

		if (!in_array($event->getTaskType(), ['Preview', 'CropScaleMask'], true)) {
			return;
		}

		// Skip if dimensions are already set
		if ($file->getProperty('width') || $file->getProperty('height')) {
			return;
		}

		$dimensions = $this->extractPdfDimensions($file->getForLocalProcessing());
		if ($dimensions !== null && $file instanceof File) {
			$file->getMetaData()->add($dimensions);
		}
	}

	/**
	 * @return array{width: int, height: int}|null
	 */
	private function extractPdfDimensions(string $filePath): ?array
	{
		$document = $this->parser->parseFile($filePath);
		$pages = $document->getPages();
		$firstPage = current($pages);

		if ($firstPage === false) {
			return null;
		}

		$details = $firstPage->getDetails();
		$mediaBox = $details['MediaBox'] ?? null;

		if (!is_array($mediaBox)) {
			return null;
		}

		return [
			'width' => (int)($mediaBox[2] ?? 0),
			'height' => (int)($mediaBox[3] ?? 0),
		];
	}
}
