<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\Builder\OptimoleBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class OptimoleProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'optimole';
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
		return strtr(OptimoleBuilder::API_ENDPOINT_TEMPLATE, [
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

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function configure(TaskInterface $task): OptimoleBuilder
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();

		$builder = new OptimoleBuilder($this->getKey());
		$builder->setSource($this->source->getSource($file));

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

		$builder->setType($type);

		if (isset($configuration['crop'])) {
			$builder->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				[
					OptimoleUri::GRAVITY_TOP_LEFT,
					(int) $configuration['crop']->getOffsetLeft(),
					(int) $configuration['crop']->getOffsetTop(),
				],
			);
		}

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$builder->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['minWidth'])) {
			$builder->setMinWidth((int) $configuration['minWidth']);
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$builder->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['minHeight'])) {
			$builder->setMinHeight((int) $configuration['minHeight']);
		}

		$builder->setHash($file->getSha1());

		return $builder;
	}

	private function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'api_key' => null,
			'source_uri' => null,
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
			'image/ico',
			'image/heic',
			'image/heif',
			'image/bmp',
			'image/tiff',
			'application/pdf',
			'video/youtube',
			'video/vimeo',
		];
	}
}
