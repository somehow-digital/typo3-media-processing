<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgixUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ImgixImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'imgix';
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
			'source_loader' => 'folder',
			'source_uri' => null,
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
			'image/png',
			'image/webp',
			'image/gif',
			'image/heic',
			'image/bmp',
			'image/eps',
			'image/tiff',
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

		$uri = new ImgixUri(
			$this->getEndpoint(),
			$this->getSignatureKey(),
		);

		$uri->setSource($this->source->getSource($file));

		$fit = (static function ($configuration) {
			switch (true) {
				default:
					return 'scale';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'crop';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'clip';
			}
		})($configuration);

		$uri->setFit($fit);

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$uri->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$uri->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['crop'])) {
			$uri->setRect(
				(int) ($configuration['crop']->getOffsetLeft()),
				(int) ($configuration['crop']->getOffsetTop()),
				(int) ($configuration['crop']->getWidth()),
				(int) ($configuration['crop']->getHeight()),
			);
		}

		return new ImageServiceResult(
			$uri,
			$dimension,
		);
	}
}
