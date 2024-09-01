<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\GumletUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Utility\FocusAreaUtility;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class GumletImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'gumlet';
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
			'api_endpoint' => null,
			'signature' => false,
			'signature_key' => null,
		]);
	}

	public function getEndpoint(): string
	{
		return $this->options['api_endpoint'];
	}

	public function getSignatureKey(): ?string
	{
		return $this->options['signature_key'] ?: null;
	}

	public function hasConfiguration(): bool
	{
		return filter_var($this->getEndpoint(), FILTER_VALIDATE_URL) !== false;
	}

	public function getSupportedMimeTypes(): array
	{
		return [
			'image/jpeg',
			'image/jpx',
			'image/jpm',
			'image/jxl',
			'image/png',
			'image/webp',
			'image/tiff',
			'image/gif',
			'image/heic',
			'image/heif',
			'image/avif',
			'application/pdf',
		];
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		return
			$task->getSourceFile()->getStorage()?->isPublic() &&
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function processTask(TaskInterface $task): ImageServiceResult
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new GumletUri(
			$this->getEndpoint(),
			$this->getSignatureKey(),
		);

		$uri->setSource($this->source->getSource($file));

		if (isset($configuration['crop'])) {
			$uri->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				(int) $configuration['crop']->getOffsetLeft(),
				(int) $configuration['crop']->getOffsetTop(),
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

			$uri->setGravity($horizontalOffset, $verticalOffset);
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
