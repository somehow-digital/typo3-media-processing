<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\Typo3\MediaProcessing\Builder\BuilderInterface;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgLabBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ImgLabProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'imglab';
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

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function configure(TaskInterface $task): BuilderInterface
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();

		$builder = new ImgLabBuilder(
			$this->getEndpoint(),
			$this->hasSignature() ? $this->getSignatureKey() : null,
			$this->hasSignature() ? $this->getSignatureSalt() : null,
		);

		$builder->setSource($this->source->getSource($file));

		$mode = (static function ($configuration) {
			switch (true) {
				default:
					return 'clip';

				case str_ends_with((string) ($configuration['width'] ?? ''), 'c'):
				case str_ends_with((string) ($configuration['height'] ?? ''), 'c'):
					return 'crop';
			}
		})($configuration);

		$builder->setMode($mode);

		if (isset($configuration['width']) || isset($configuration['maxWidth']) ) {
			$width = (int) ($configuration['width'] ?? $configuration['maxWidth'] ?? null);
			$builder->setWidth($width);
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight']) ) {
			$height = (int) ($configuration['height'] ?? $configuration['maxHeight'] ?? null);
			$builder->setHeight($height);
		}

		if (isset($configuration['crop'])) {
			$builder->setRegion(
				(int) $configuration['crop']->getOffsetLeft(),
				(int) $configuration['crop']->getOffsetTop(),
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
			);
		}

		return $builder;
	}

	private function getSupportedMimeTypes(): array
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

	private function configureOptions(OptionsResolver $resolver): void
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
}
