<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Processor;

use Psr\EventDispatcher\EventDispatcherInterface;
use SomehowDigital\Typo3\MediaProcessing\Event\MediaProcessedEvent;
use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MediaProcessor implements ProcessorInterface
{
	private readonly ?array $configuration;

	public function __construct(
		private readonly ProviderInterface $provider,
		private readonly EventDispatcherInterface $dispatcher,
		?ExtensionConfiguration $configuration,
	) {
		$this->configuration = $configuration?->get('media_processing');
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		$context = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);

		if ($context->isBackend() && !$this->configuration['common']['backend']) return false;
		if ($context->isFrontend() && !$this->configuration['common']['frontend']) return false;

		if (!$task->getSourceFile()->getStorage()?->isOnline()) return false;
		if (!$task->getSourceFile()->getStorage()?->isPublic() && !$this->configuration['common']['private']) return false;

		if (!$task->getSourceFile()->exists()) return false;
		if (!$task->getSourceFile()->getProperty('width')) return false;
		if (!$task->getSourceFile()->getProperty('height')) return false;

		if (!$this->provider->hasConfiguration()) return false;
		if (!$this->provider->supports($task)) return false;

		return true;
	}

	public function processTask(TaskInterface $task): void
	{
		if ($task->getConfigurationChecksum() === $task->getTargetFile()->getProperty('checksum')) {
			$task->setExecuted(true);
			return;
		}

		$dimension = ImageDimension::fromProcessingTask($task);

		$builder = $this->provider->configure($task);

		$event = $this->dispatcher->dispatch(new MediaProcessedEvent($task, $builder));

		$builder = $event->getBuilder() ?? $builder;

		$task->getTargetFile()->setName($task->getTargetFileName());

		$task->getTargetFile()->updateProperties([
			'width' => $dimension->getWidth(),
			'height' => $dimension->getHeight(),
			'checksum' => $task->getConfigurationChecksum(),
			'processing_url' => $builder->build(),
		]);

		if ($this->configuration['common']['storage']) {
			$this->storeFile($task, $builder->build(), $task->getConfigurationChecksum());
		}

		$task->setExecuted(true);
	}

	private function storeFile(TaskInterface $task, string $url, string $checksum): void
	{
		$contents = file_get_contents($url);

		if ($contents) {
			$path = GeneralUtility::tempnam($checksum);

			if ($path) {
				file_put_contents($path, $contents);
				$task->getTargetFile()->updateWithLocalFile($path);
				GeneralUtility::unlink_tempfile($path);
			}
		}
	}
}
