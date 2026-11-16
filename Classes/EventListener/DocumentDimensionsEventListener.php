<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use Smalot\PdfParser\Parser;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;

class DocumentDimensionsEventListener
{
	private ?array $configuration;

	public function __construct(
		private readonly ?ProviderInterface $provider,
		private readonly Parser $parser,
		?ExtensionConfiguration $configuration,
	) {
		$this->configuration = $configuration?->get('media_processing');
	}

	public function __invoke(BeforeFileProcessingEvent $event): void
	{
		$file = $event->getFile();

		if (!$file instanceof File) {
			return;
		}

		$context = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);

		if ($context->isBackend() && !$this->configuration['common']['backend']) {
			return;
		}
		if ($context->isFrontend() && !$this->configuration['common']['frontend']) {
			return;
		}

		if (!$file->getStorage()->isOnline()) {
			return;
		}
		if (!$file->getStorage()->isPublic() && !$this->configuration['common']['private']) {
			return;
		}

		if (!$file->exists()) {
			return;
		}
		if ($file->getProperty('width')) {
			return;
		}
		if ($file->getProperty('height')) {
			return;
		}

		if (!$this->provider?->hasConfiguration()) {
			return;
		}
		if (!in_array($event->getTaskType(), ['Preview', 'CropScaleMask'], true)) {
			return;
		}

		$document = $this->parser->parseFile($file->getForLocalProcessing());
		$details = current($document->getPages())->getDetails();

		if ($details['MediaBox']) {
			$file->getMetaData()->add([
				'width' => (int) $details['MediaBox'][2],
				'height' => (int) $details['MediaBox'][3],
			]);
		}
	}
}
