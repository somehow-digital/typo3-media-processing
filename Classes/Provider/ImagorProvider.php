<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\Builder\ImagorBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ImagorProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'imagor';
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

	public function getSignatureAlgorithm(): ?string
	{
		return $this->options['signature_algorithm'] ?: null;
	}

	public function getSignatureLength(): ?int
	{
		return (int) $this->options['signature_length'] ?: null;
	}

	public function hasConfiguration(): bool
	{
		return filter_var($this->getEndpoint(), FILTER_VALIDATE_URL) !== false;
	}

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function configure(TaskInterface $task): ImagorBuilder
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();

		$builder = new ImagorBuilder(
			$this->getEndpoint(),
			$this->getSignatureKey(),
			$this->getSignatureAlgorithm(),
			$this->getSignatureLength(),
		);

		$builder->setSource($this->source->getSource(($file)));

		$type = (static function ($configuration) {
			switch (true) {
				default:
					return 'stretch';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'm'):
				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
				case isset($configuration['maxWidth']):
				case isset($configuration['maxHeight']):
					return 'fit-in';
			}
		})($configuration);

		$type && $builder->setType($type);

		if (isset($configuration['crop'])) {
			$builder->setCrop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				(int) $configuration['crop']->getOffsetLeft(),
				(int) $configuration['crop']->getOffsetTop(),
			);
		}

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$builder->setWidth((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$builder->setHeight((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		return $builder;
	}

	private function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'api_endpoint' => null,
			'source_loader' => 'uri',
			'source_uri' => null,
			'signature' => false,
			'signature_key' => null,
			'signature_algorithm' => 'sha1',
			'signature_length' => null,
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
			'image/bmp',
			'image/tiff',
			'video/youtube',
			'video/vimeo',
		];
	}
}
