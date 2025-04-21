<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\ProcessedFile;

class FileReprocessingEventListener
{
	private ?ImageServiceInterface $service;

	private ?array $configuration;

	public function __construct(
		?ImageServiceInterface $service,
		?ExtensionConfiguration $configuration,
	) {
		$this->service = $service;
		$this->configuration = $configuration?->get('media_processing');
	}

	public function __invoke(BeforeFileProcessingEvent $event): void
	{
		$context = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);
		$file = $event->getProcessedFile();

		if ($context->isBackend() && !$this->configuration['common']['backend']) return;
		if ($context->isFrontend() && !$this->configuration['common']['frontend']) return;

		if (!$event->getFile()->getStorage()?->isOnline()) return;
		if (!$event->getFile()->getStorage()?->isPublic() && !$this->configuration['common']['private']) return;

		if (!$event->getFile()->exists()) return;
		if (!$event->getFile()->getProperty('width')) return;
		if (!$event->getFile()->getProperty('height')) return;

		if (!$this->service?->hasConfiguration()) return;
		if (!$this->service?->canProcessTask($event->getProcessedFile()->getTask())) return;

		if ($this->needsReprocessing($file)) {
			$file->delete();
		} else {
			$file->setName($file->getName());
		}
	}

	private function needsReprocessing(ProcessedFile $file): bool
	{
		return
			$file->getProperty('integration') !== $this->service::getIdentifier() ||
			$file->getProperty('integration_checksum') !== $this->service->calculateChecksum($file->getOriginalFile());
	}
}
