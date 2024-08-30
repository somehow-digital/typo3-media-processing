<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\OptimoleUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Utility\FocusAreaUtility;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class OptimoleImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'optimole';
	}

	public function __construct(
		protected readonly UriSourceInterface $source,
		protected array $options,
	) {
		$resolver = new OptionsResolver();
		$this->configureOptions($resolver);
		$this->options = $resolver->resolve($options);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'api_key' => null,
			'source_uri' => null,
		]);
	}

	public function getEndpoint(): string
	{
		return strtr(OptimoleUri::API_ENDPOINT_TEMPLATE, [
			'%key%' => $this->getKey(),
		]);
	}

	public function getKey(): ?string
	{
		return $this->options['api_key'] ?: null;
	}

	public function hasConfiguration(): bool
	{
		return (bool) $this->options['api_key'];
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		return
			$task->getSourceFile()->getStorage()?->isPublic() &&
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), [
				'image/jpeg',
				'image/png',
				'image/webp',
				'image/avif',
				'image/gif',
				'image/ico',
				'image/heic',
				'image/heif',
				'image/bmp',
				'image/tiff',
				'application/pdf',
			]);
	}

	public function processTask(TaskInterface $task): ImageServiceResult
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new OptimoleUri($this->getKey());
		$uri->setSource($this->source->getSource($file));

		$type = (static function ($configuration) {
			switch (true) {
				default:
					return 'force';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'fit';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'fill';
			}
		})($configuration);

		$uri->setType($type);

		if (isset($configuration['crop'])) {
			$uri->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				[
					OptimoleUri::GRAVITY_TOP_LEFT,
					(int) $configuration['crop']->getOffsetLeft(),
					(int) $configuration['crop']->getOffsetTop(),
				],
			);
		}

		if (isset($configuration['focusArea']) && !$configuration['focusArea']->isEmpty()) {
			$horizontalOffset = FocusAreaUtility::calculateCenter(
				$configuration['focusArea']->getOffsetLeft(),
				$configuration['focusArea']->getWidth(),
			);
			$verticalOffset = FocusAreaUtility::calculateCenter(
				$configuration['focusArea']->getOffsetTop(),
				$configuration['focusArea']->getHeight(),
			);
			$uri->setGravity('fp', $horizontalOffset, $verticalOffset);
		}

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$uri->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['minWidth'])) {
			$uri->setMinWidth((int) $configuration['minWidth']);
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$uri->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['minHeight'])) {
			$uri->setMinHeight((int) $configuration['minHeight']);
		}

		$uri->setHash($file->getSha1());

		return new ImageServiceResult(
			$uri,
			$dimension,
		);
	}
}
