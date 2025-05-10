<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ImgProxyProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'imgproxy';
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
			'source_loader' => 'uri',
			'source_uri' => null,
			'encryption' => false,
			'encryption_key' => null,
			'signature' => false,
			'signature_key' => null,
			'signature_salt' => null,
			'signature_size' => 0,
			'processing_pdf' => false,
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

	public function getSignatureSalt(): ?string
	{
		return $this->options['signature_salt'] ?: null;
	}

	public function getSignatureSize(): ?int
	{
		return (int) $this->options['signature_size'] ?: null;
	}

	public function getEncryptionKey(): ?string
	{
		return $this->options['encryption_key'] ?: null;
	}

	public function hasConfiguration(): bool
	{
		return filter_var($this->getEndpoint(), FILTER_VALIDATE_URL) !== false;
	}

	private function getSupportedMimeTypes(): array
	{
		return array_filter([
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
			'video/youtube',
			'video/vimeo',
			$this->options['processing_pdf'] ? 'application/pdf' : null,
		]);
	}

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function process(TaskInterface $task): ProviderResultInterface
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new ImgProxyUri(
			$this->getEndpoint(),
			$this->getSignatureKey(),
			$this->getSignatureSalt(),
			$this->getSignatureSize(),
			$this->getEncryptionKey(),
		);

		$uri->setSource($this->source->getSource(($file)));

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
					ImgProxyUri::GRAVITY_TOP_LEFT,
					(int) $configuration['crop']->getOffsetLeft(),
					(int) $configuration['crop']->getOffsetTop(),
				],
			);
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

		if (isset($configuration['dpr']) && $configuration['dpr'] > 1) {
			$uri->setDevicePixelRatio((float) $configuration['dpr']);
		}

		$uri->setHash($file->getSha1());

		return new ProviderResult(
			$uri,
			$dimension,
		);
	}
}
