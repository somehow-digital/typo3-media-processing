<?php

use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderFactory;
use SomehowDigital\Typo3\MediaProcessing\Processor\MediaProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function () {
	$provider = GeneralUtility::makeInstance(ProviderFactory::class)();

	if ($provider?->hasConfiguration()) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'][MediaProcessor::class] = [
			'className' => MediaProcessor::class,
			'before' => [
				'LocalImageProcessor',
				'DeferredBackendImageProcessor',
				'OnlineMediaPreviewProcessor',
			],
		];
	}
})();
