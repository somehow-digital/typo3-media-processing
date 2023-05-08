<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriBuilderInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class CloudflareImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'cloudflare';
	}

	public function __construct(
		protected readonly string $endpoint,
		protected readonly UriBuilderInterface $builder,
	) {
	}

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function hasConfiguration(): bool
	{
		return filter_var($this->getEndpoint(), FILTER_VALIDATE_URL) !== false;
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
				'image/gif',
			]);
	}
}
