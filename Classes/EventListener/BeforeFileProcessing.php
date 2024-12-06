<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use Smalot\PdfParser\Parser;
use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\ProcessedFile;

class BeforeFileProcessing
{
	private ?ImageServiceInterface $service;

	private ?array $configuration;

	private Parser $parser;

	public function __construct(
		?ImageServiceInterface $service,
		?ExtensionConfiguration $configuration,
		Parser $parser,
	) {
		$this->service = $service;
		$this->configuration = $configuration?->get('media_processing');
		$this->parser = $parser;
	}

	public function __invoke(BeforeFileProcessingEvent $event): void
	{
		$context = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);
		$file = $event->getProcessedFile();

		if (
			(
				$context->isBackend() && $this->configuration['common']['backend'] ||
				$context->isFrontend() && $this->configuration['common']['frontend']
			) &&
			$event->getFile()->exists() &&
			$this->service?->hasConfiguration() &&
			$this->service?->canProcessTask($file->getTask())
		) {
			if (
				$event->getFile()->getMimeType() === 'application/pdf' &&
				!$event->getFile()->getProperty('width') &&
				!$event->getFile()->getProperty('height')
			) {
				$document = $this->parser->parseFile($event->getFile()->getForLocalProcessing());
				$details = current($document->getPages())->getDetails();

				$event->getFile()->getMetaData()->add([
					'width' => (int) $details['MediaBox'][2] ?: 0,
					'height' => (int) $details['MediaBox'][3] ?: 0,
				]);
			}

			if ($this->needsReprocessing($file)) {
				$file->delete();
			} else {
				$file->setName($file->getName());
			}
		}
	}

	private function needsReprocessing(ProcessedFile $file): bool
	{
		$checksum = sha1(
			$this->service->getIdentifier() .
			$file->getOriginalFile()->getIdentifier() .
			$file->getOriginalFile()->getSize() .
			serialize($this->configuration)
		);

		return $file->getProperty('integration_checksum') !== $checksum;
	}
}
