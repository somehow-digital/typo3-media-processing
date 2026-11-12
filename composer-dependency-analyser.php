<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
	// Provided by the TYPO3 core.
	->ignoreErrorsOnExtensions(['ext-filter', 'ext-mbstring'], [ErrorType::SHADOW_DEPENDENCY])
	->ignoreErrorsOnPackage('psr/event-dispatcher', [ErrorType::SHADOW_DEPENDENCY])
	// Required by typo3/testing-framework.
	->ignoreErrorsOnPackage('phpunit/phpunit', [ErrorType::SHADOW_DEPENDENCY]);
