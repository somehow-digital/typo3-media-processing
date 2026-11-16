<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Provider;

use SomehowDigital\ImgProxy\Masker\EncryptionMasker;
use SomehowDigital\ImgProxy\Option\CacheBuster;
use SomehowDigital\ImgProxy\Option\Crop;
use SomehowDigital\ImgProxy\Option\Dpr;
use SomehowDigital\ImgProxy\Option\Gravity;
use SomehowDigital\ImgProxy\Option\GravityEnum;
use SomehowDigital\ImgProxy\Option\Height;
use SomehowDigital\ImgProxy\Option\MinHeight;
use SomehowDigital\ImgProxy\Option\MinWidth;
use SomehowDigital\ImgProxy\Option\ResizingType;
use SomehowDigital\ImgProxy\Option\ResizingTypeEnum;
use SomehowDigital\ImgProxy\Option\Width;
use SomehowDigital\ImgProxy\Signer\Signer;
use SomehowDigital\ImgProxy\Url;
use SomehowDigital\Typo3\MediaProcessing\Builder\BuilderInterface;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgProxyBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ImgProxyProvider implements ProviderInterface
{
	public static function getIdentifier(): string
	{
		return 'imgproxy';
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
		return $this->getEndpoint() !== '';
	}

	public function supports(TaskInterface $task): bool
	{
		return
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
			&& in_array($task->getSourceFile()->getMimeType(), $this->getSupportedMimeTypes(), true);
	}

	public function configure(TaskInterface $task): BuilderInterface
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();

		$key = $this->getSignatureKey();
		$salt = $this->getSignatureSalt();
		$size = $this->getSignatureSize();
		$secret = $this->getEncryptionKey();

		$signer = null;

		if ($key && $salt) {
			$signer = $size === null ? new Signer($key, $salt) : new Signer($key, $salt, $size);
		}

		$type = (static fn(array $configuration): ResizingTypeEnum => match (true) {
			str_ends_with((string) ($configuration['width'] ?? ''), 'm'), str_ends_with((string) ($configuration['height'] ?? ''), 'm'), isset($configuration['maxWidth']), isset($configuration['maxHeight']) => ResizingTypeEnum::FIT,
			str_ends_with((string) ($configuration['width'] ?? ''), 'c'), str_ends_with((string) ($configuration['height'] ?? ''), 'c') => ResizingTypeEnum::FILL,
			default => ResizingTypeEnum::FORCE,
		})($configuration);

		$options = [new ResizingType($type)];

		if (isset($configuration['width']) || isset($configuration['maxWidth'])) {
			$options[] = new Width((int) ($configuration['width'] ?? $configuration['maxWidth']));
		}

		if (isset($configuration['minWidth'])) {
			$options[] = new MinWidth((int) $configuration['minWidth']);
		}

		if (isset($configuration['height']) || isset($configuration['maxHeight'])) {
			$options[] = new Height((int) ($configuration['height'] ?? $configuration['maxHeight']));
		}

		if (isset($configuration['minHeight'])) {
			$options[] = new MinHeight((int) $configuration['minHeight']);
		}

		if (isset($configuration['crop'])) {
			$options[] = new Crop(
				(int) $configuration['crop']->getWidth(),
				(int) $configuration['crop']->getHeight(),
				new Gravity(
					GravityEnum::NORTH_WEST,
					(int) $configuration['crop']->getOffsetLeft(),
					(int) $configuration['crop']->getOffsetTop(),
				),
			);
		}

		if (isset($configuration['dpr']) && $configuration['dpr'] > 1) {
			$options[] = new Dpr((float) $configuration['dpr']);
		}

		$options[] = new CacheBuster($file->getSha1());

		$url = Url::create($secret ? new EncryptionMasker($secret) : null, $signer)
			->source($this->source->getSource($file))
			->options(...$options);

		return new ImgProxyBuilder($this->getEndpoint(), $url);
	}

	private function configureOptions(OptionsResolver $resolver): void
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
}
