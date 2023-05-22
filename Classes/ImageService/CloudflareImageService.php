<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudflareUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class CloudflareImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'cloudflare';
	}

	public function __construct(
		protected readonly string $endpoint,
		protected readonly UriSourceInterface $source,
	) {
	}

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function hasConfiguration(): bool
	{
		return filter_var($this->getEndpoint(), FILTER_VALIDATE_URL) !== false;
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		return
			$task->getSourceFile()->exists() &&
			$task->getSourceFile()->getStorage()?->isPublic() &&
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), [
				'image/jpeg',
				'image/png',
				'image/webp',
				'image/gif',
			]);
	}

	public function processTask(TaskInterface $task): ImageServiceResult
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new CloudflareUri($this->getEndpoint());
		$uri->setSource($this->source->getSource($file));

		$fit = (static function ($configuration) {
			switch (true) {
				default:
					return 'contain';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'cover';
			}
		})($configuration);

		$uri->setFit($fit);

		if (isset($configuration['crop'])) {
			$uri->setTrim(
				(int) $configuration['crop']->getOffsetTop(),
				(int) ($file->getProperty('width') - $configuration['crop']->getWidth() - $configuration['crop']->getOffsetLeft()),
				(int) ($file->getProperty('height') - $configuration['crop']->getHeight() - $configuration['crop']->getOffsetTop()),
				(int) $configuration['crop']->getOffsetLeft(),
			);
		}

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$uri->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$uri->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		return new ImageServiceResult(
			$uri,
			$dimension,
		);
	}
}
