<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\OptimoleUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriBuilderInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class OptimoleImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'optimole';
	}

	public function __construct(
		protected readonly string $key,
		protected readonly UriBuilderInterface $builder,
	) {
	}

	public function getEndpoint(): string
	{
		return strtr(OptimoleUri::API_ENDPOINT_TEMPLATE, [
			'%key%' => $this->key,
		]);
	}

	public function hasConfiguration(): bool
	{
		return (bool) $this->key;
	}

	public function canProcessTask(TaskInterface $task): bool
	{
		return
			$task->getSourceFile()->exists() &&
			$task->getSourceFile()->getStorage()?->isPublic() &&
			in_array($task->getName(), ['Preview', 'CropScaleMask'], true) &&
			in_array($task->getSourceFile()->getMimeType(), [
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
			]);
	}
}
