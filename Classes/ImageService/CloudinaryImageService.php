<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudinaryUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class CloudinaryImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'cloudinary';
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
			'delivery_mode' => 'fetch',
			'source_uri' => null,
			'signature' => false,
			'signature_algorithm' => 'sha1',
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

	public function getSignatureAlgorithm(): ?string
	{
		return $this->options['signature_algorithm'] ?: null;
	}

	public function getSource(): ?UriSourceInterface
	{
		return $this->source;
	}

	public function hasConfiguration(): bool
	{
		return (bool) $this->getEndpoint();
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		return
			$task->getSourceFile()->getStorage()?->isPublic() &&
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), [
				'image/jpeg',
				'image/jp2',
				'image/jpx',
				'image/jpm',
				'image/vnd.ms-photo',
				'image/png',
				'image/webp',
				'image/avif',
				'image/gif',
				'image/ico',
				'image/heic',
				'image/heif',
				'image/bmp',
				'image/tiff',
				'image/x-targa',
				'image/x-tga',
				'application/pdf',
			]);
	}

	public function processTask(TaskInterface $task): ImageServiceResult
	{
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new CloudinaryUri(
			$this->getEndpoint(),
			$this->getSource(),
			$this->getSignatureKey(),
			$this->getSignatureAlgorithm(),
		);

		$uri->setSource($this->source->getSource($task->getSourceFile()));

		$fit = (static function ($configuration) {
			switch (true) {
				default:
					return 'scale';

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

		$uri->setMode($fit);

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$uri->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$uri->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['crop'])) {
			$uri->setCrop(
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
