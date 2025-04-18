<?php

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceFactory;
use SomehowDigital\Typo3\MediaProcessing\Processor\MediaProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function () {
	$service = GeneralUtility::makeInstance(ImageServiceFactory::class)();

	if ($service?->hasConfiguration()) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'][MediaProcessor::class] = [
			'className' => MediaProcessor::class,
			'before' => ['LocalImageProcessor', 'DeferredBackendImageProcessor', 'OnlineMediaPreviewProcessor'],
		];
	}
})();
