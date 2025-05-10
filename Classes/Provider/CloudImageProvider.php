<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\CloudImageUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class CloudImageProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'cloudimage';
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

	public function getSource(): ?UriSourceInterface
	{
		return $this->source;
	}

	public function hasConfiguration(): bool
	{
		return (bool) $this->getEndpoint();
	}

	private function getSupportedMimeTypes(): array
	{
		return [
			'image/jpeg',
			'image/png',
			'image/webp',
			'image/avif',
			'image/gif',
			'application/pdf',
			'video/youtube',
			'video/vimeo',
		];
	}

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function process(TaskInterface $task): ProviderResultInterface
	{
		$configuration = $task->getTargetFile()->getProcessingConfiguration();
		$dimension = ImageDimension::fromProcessingTask($task);

		$uri = new CloudImageUri(
			$this->getEndpoint(),
			$this->getSignatureKey(),
		);

		$uri->setSource($this->source->getSource($task->getSourceFile()));

		$function = (static function ($configuration) {
			switch (true) {
				default:
					return 'cover';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'crop';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'bound';
			}
		})($configuration);

		$uri->setFunction($function);

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

		return new ProviderResult(
			$uri,
			$dimension,
		);
	}
}
