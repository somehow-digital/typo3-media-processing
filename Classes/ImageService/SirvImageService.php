<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\SirvUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class SirvImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'sirv';
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
				'image/gif',
				'image/webp',
				'image/heic',
				'image/bmp',
				'image/eps',
				'image/tiff',
				'application/pdf',
			]);
	}

	public function processTask(TaskInterface $task): ImageServiceResult
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new SirvUri($this->getEndpoint());
		$uri->setSource($this->source->getSource($file));

		$scale = (static function ($configuration) {
			switch (true) {
				default:
					return 'ignore';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'fill';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'fit';
			}
		})($configuration);

		$uri->setScale($scale);

		$width = (int) ($configuration['width'] ?? $configuration['maxWidth'] ?? null);
		$height = (int) ($configuration['height'] ?? $configuration['maxHeight'] ?? null);

		$uri->setWidth($width);
		$uri->setHeight($height);

		if (isset($configuration['crop'])) {
			$width = $file->getProperty('width') / $configuration['crop']->getWidth() * $dimension->getWidth();
			$height = $file->getProperty('height') / $configuration['crop']->getHeight() * $dimension->getHeight();

			$crop = [
				(int) ($configuration['crop']->getWidth() / $file->getProperty('width') * $width),
				(int) ($configuration['crop']->getHeight() / $file->getProperty('height') * $height),
				(int) ($configuration['crop']->getOffsetLeft() / $file->getProperty('width') * $width),
				(int) ($configuration['crop']->getOffsetTop() / $file->getProperty('height') * $height),
			];

			$uri->setWidth((int) $width);
			$uri->setHeight((int) $height);
			$uri->setCrop(...$crop);
		}

		return new ImageServiceResult(
			$uri,
			$dimension,
		);
	}
}
