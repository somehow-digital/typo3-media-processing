<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\ImageService;

use SomehowDigital\Typo3\MediaProcessing\UriBuilder\ImgProxyUri;
use SomehowDigital\Typo3\MediaProcessing\UriBuilder\UriSourceInterface;
use SomehowDigital\Typo3\MediaProcessing\Utility\FocusAreaUtility;
use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask;
use TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImgProxyImageService extends ImageServiceAbstract
{
	public static function getIdentifier(): string
	{
		return 'imgproxy';
	}

	public function __construct(
		protected readonly string $endpoint,
		protected readonly UriSourceInterface $source,
		protected readonly ?string $key,
		protected readonly ?string $salt,
		protected readonly ?int $size,
		protected readonly ?string $secret,
	) {
	}

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function getKey(): ?string
	{
		return $this->key;
	}

	public function getSignatureSalt(): ?string
	{
		return $this->salt;
	}

	public function getSignatureSize(): ?int
	{
		return $this->size;
	}

	public function getEncryptionSecret(): ?string
	{
		return $this->secret;
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

	protected function getHelperByTaskName($taskName)
	{
		switch ($taskName) {
			case 'Preview':
				$helper = GeneralUtility::makeInstance(LocalPreviewHelper::class);
				break;
			case 'CropScaleMask':
				$helper = GeneralUtility::makeInstance(LocalCropScaleMaskHelper::class);
				break;
			default:
				throw new \InvalidArgumentException('Cannot find helper for task name: "' . $taskName . '"', 1353401352);
		}

		return $helper;
	}

	public function processTask(TaskInterface $task): ImageServiceResult
	{
		$file = $task->getSourceFile();
		$configuration = $task->getTargetFile()->getProcessingConfiguration();

		try {
			$dimension = ImageDimension::fromProcessingTask($task);
		} catch (ZeroImageDimensionException $e){

			/**
			 * PDF files sometimes do not contain the dimensions of the files
			 * So we retrieve them using the GraphicalFunctions helper
			 */

			$absoluteFilePath = $file->getForLocalProcessing(false);

			/** @var ImagePreviewTask $imagePreviewTask */
			$helper = $this->getHelperByTaskName($task->getName());
			$result = $helper->processWithLocalFile($task, $absoluteFilePath);

			if(!empty($result['filePath']) && file_exists($result['filePath'])){
				$graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
				$imageDimensions = $graphicalFunctions->getImageDimensions($result['filePath']);
				$configuration['width'] = $imageDimensions[0] ?? 0;
				$configuration['height'] = $imageDimensions[1] ?? 0;
			}
			$dimension = new ImageDimension($configuration['width'], $configuration['height']);
		}

		$uri = new ImgProxyUri(
			$this->getEndpoint(),
			$this->getKey(),
			$this->getSignatureSalt(),
			$this->getSignatureSize(),
			$this->getEncryptionSecret(),
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

		if (isset($configuration['focusArea']) && !$configuration['focusArea']->isEmpty()) {
			$horizontalOffset = FocusAreaUtility::calculateCenter(
				$configuration['focusArea']->getOffsetLeft(),
				$configuration['focusArea']->getWidth(),
			);
			$verticalOffset = FocusAreaUtility::calculateCenter(
				$configuration['focusArea']->getOffsetTop(),
				$configuration['focusArea']->getHeight(),
			);
			$uri->setGravity('fp', $horizontalOffset, $verticalOffset);
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

		$uri->setHash($file->getSha1());

		return new ImageServiceResult(
			$uri,
			$dimension,
		);
	}
}
