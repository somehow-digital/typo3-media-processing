<?php

declare(strict_types=1);

use SomehowDigital\Typo3\MediaProcessing\Controller\InvalidationController;

return [
	'somehowdigital_media_processing_invalidation' => [
		'path' => '/somehowdigital_media_processing/invalidation',
		'methods' => ['POST'],
		'target' => InvalidationController::class.'::__invoke',
	],
];
