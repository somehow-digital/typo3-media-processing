<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\Builder\CloudImageBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class CloudImageProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'cloudimage';
	}

	public function __construct(
		protected readonly SourceInterface $source,
		protected array $options,
	) {
		$resolver = new OptionsResolver();
		$this->configureOptions($resolver);
		$this->options = $resolver->resolve($options);
	}

	public function getEndpoint(): string
	{
		return $this->options['api_endpoint'];
	}

	public function getSignatureKey(): ?string
	{
		return $this->options['signature_key'] ?: null;
	}

	public function getSource(): ?SourceInterface
	{
		return $this->source;
	}

	public function hasConfiguration(): bool
	{
		return (bool) $this->getEndpoint();
	}

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function configure(TaskInterface $task): CloudImageBuilder
	{
		$configuration = $task->getTargetFile()->getProcessingConfiguration();

		$builder = new CloudImageBuilder(
			$this->getEndpoint(),
			$this->getSignatureKey(),
		);

		$builder->setSource($this->source->getSource($task->getSourceFile()));

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

		$builder->setFunction($function);

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$builder->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$builder->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['crop'])) {
			$builder->setCrop(
				(int) ($configuration['crop']->getOffsetLeft()),
				(int) ($configuration['crop']->getOffsetTop()),
				(int) ($configuration['crop']->getWidth()),
				(int) ($configuration['crop']->getHeight()),
			);
		}

		return $builder;
	}

	private function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'api_endpoint' => null,
			'source_uri' => null,
			'signature' => false,
			'signature_key' => null,
		]);
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
}
