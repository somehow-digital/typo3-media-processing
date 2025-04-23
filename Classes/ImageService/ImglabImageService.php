<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImglabUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ImglabImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'imglab';
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
			'source_loader' => 'web',
			'source_uri' => null,
			'signature' => false,
			'signature_key' => null,
			'signature_salt' => null,
		]);
	}

	public function getEndpoint(): string
	{
		return $this->options['api_endpoint'];
	}

	public function hasSignature(): bool
	{
		return (bool) $this->options['signature'];
	}

	public function getSignatureKey(): ?string
	{
		return $this->options['signature_key'] ?: null;
	}

	public function getSignatureSalt(): ?string
	{
		return $this->options['signature_salt'] ?: null;
	}

	public function hasConfiguration(): bool
	{
		return filter_var($this->getEndpoint(), FILTER_VALIDATE_URL) !== false;
	}

	public function getSupportedMimeTypes(): array
	{
		return [
			'image/jpeg',
			'image/jp2',
			'image/jpx',
			'image/jpm',
			'image/png',
			'image/gif',
			'image/webp',
			'image/heic',
			'application/pdf',
			'video/youtube',
			'video/vimeo',
		];
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function processTask(TaskInterface $task): ImageServiceResultInterface
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new ImglabUri(
			$this->getEndpoint(),
			$this->hasSignature() ? $this->getSignatureKey() : null,
			$this->hasSignature() ? $this->getSignatureSalt() : null,
		);

		$uri->setSource($this->source->getSource($file));

		$mode = (static function ($configuration) {
			switch (true) {
				default:
					return 'clip';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'crop';
			}
		})($configuration);

		$uri->setMode($mode);

		if (isset($configuration['crop'])) {
			$uri->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				(int) $configuration['crop']->getOffsetLeft(),
				(int) $configuration['crop']->getOffsetTop(),
			);
		}

		$width = (int) ($configuration['width'] ?? $configuration['maxWidth'] ?? null);
		$height = (int) ($configuration['height'] ?? $configuration['maxHeight'] ?? null);

		$uri->setWidth($width);
		$uri->setHeight($height);

		return new ImageServiceResult(
			$uri,
			$dimension,
		);
	}
}
