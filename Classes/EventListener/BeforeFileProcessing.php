<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\ProcessedFile;

class BeforeFileProcessing
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

		if (
			(
				($context->isBackend() && $this->configuration['common']['backend']) ||
				($context->isFrontend() && $this->configuration['common']['frontend'])
			) &&
			(
				$file->getTask()->getSourceFile()->exists() &&
				$file->getTask()->getSourceFile()->getProperty('width') &&
				$file->getTask()->getSourceFile()->getProperty('height')
			) &&
			$this->service?->hasConfiguration() &&
			$this->service?->canProcessTask($file->getTask())
		) {
			if ($this->needsReprocessing($file)) {
				$file->delete();
			} else {
				$file->setName($file->getName());
			}
		}
	}

	private function needsReprocessing(ProcessedFile $file): bool
	{
		if ($file->getProperty('integration') !== $this->service?->getIdentifier()) {
			return true;
		}

		if ($file->getProperty('integration_checksum') !== sha1(serialize($this->configuration))) {
			return true;
		}

		return false;
	}
}
