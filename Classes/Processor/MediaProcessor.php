<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Processor;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MediaProcessor implements ProcessorInterface
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

	public function canProcessTask(TaskInterface $task): bool
	{
		$context = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);

		return
			(
				($context->isBackend() && $this->configuration['common']['backend']) ||
				($context->isFrontend() && $this->configuration['common']['frontend'])
			) &&
			(
				$task->getSourceFile()->getStorage()?->isOnline() &&
				(
					$task->getSourceFile()->getStorage()?->isPublic() ||
					(!$task->getSourceFile()->getStorage()?->isPublic() && $this->configuration['common']['private'])
				)
			) &&
			(
				$task->getSourceFile()->exists() &&
				$task->getSourceFile()->getProperty('width') &&
				$task->getSourceFile()->getProperty('height')
			) &&
			$this->service?->hasConfiguration() &&
			$this->service?->canProcessTask($task);
	}

	public function processTask(TaskInterface $task): void
	{
		$result = $this->service?->processTask($task);

		if ($result->getUri()) {
			$checksum = $this->service?->calculateChecksum($task->getSourceFile());

			$task->getTargetFile()->setName($task->getTargetFileName());

			$task->getTargetFile()->updateProperties([
				'width' => $result->getDimension()->getWidth(),
				'height' => $result->getDimension()->getHeight(),
				'checksum' => $task->getConfigurationChecksum(),
				'integration' => $this->service::getIdentifier(),
				'integration_checksum' => $checksum,
				'processing_url' => (string) $result->getUri(),
			]);

			if ($this->configuration['common']['storage']) {
				$this->storeFile($task, (string) $result->getUri(), $checksum);
			}

			$task->setExecuted(true);
		}
	}

	private function storeFile(TaskInterface $task, string $uri, string $checksum): void
	{
		$contents = file_get_contents($uri);

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
