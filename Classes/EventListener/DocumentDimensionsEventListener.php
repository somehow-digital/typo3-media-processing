<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use Smalot\PdfParser\Parser;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;

class DocumentDimensionsEventListener
{
	private ?array $configuration;

	public function __construct(
		private readonly ProviderInterface $provider,
		private readonly Parser $parser,
		?ExtensionConfiguration $configuration,
	) {
		$this->configuration = $configuration?->get('media_processing');
	}

	public function __invoke(BeforeFileProcessingEvent $event): void {
		$context = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);

		if ($context->isBackend() && !$this->configuration['common']['backend']) return;
		if ($context->isFrontend() && !$this->configuration['common']['frontend']) return;

		if (!$event->getFile()->getStorage()?->isOnline()) return;
		if (!$event->getFile()->getStorage()?->isPublic() && !$this->configuration['common']['private']) return;

		if (!$event->getFile()->exists()) return;
		if ($event->getFile()->getProperty('width')) return;
		if ($event->getFile()->getProperty('height')) return;

		if (!$this->provider?->hasConfiguration()) return;
		if (!$this->provider?->supports($event->getProcessedFile()->getTask())) return;

		$document = $this->parser->parseFile($event->getFile()->getForLocalProcessing());
		$details = current($document->getPages())->getDetails();

		if ($details['MediaBox']) {
			$event->getFile()->getMetaData()->add([
				'width' => (int) $details['MediaBox'][2] ?: 0,
				'height' => (int) $details['MediaBox'][3] ?: 0,
			]);
		}
	}
}
