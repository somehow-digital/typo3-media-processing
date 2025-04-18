<?php

namespace SomehowDigital\Typo3\MediaProcessing\Utility;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OnlineMediaUtility
{
	public static function getPreviewImage(FileInterface $file): ?string
	{
		$helper = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getOnlineMediaHelper($file);
		$image = $helper ? $helper->getPreviewImage($file) : null;

		return $image
			? substr($image, strpos($image, '/typo3temp/assets/online_media/'))
			: null;
	}
}
