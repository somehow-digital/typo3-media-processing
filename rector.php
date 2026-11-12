<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/Classes',
		__DIR__ . '/Configuration',
		__DIR__ . '/Tests',
	])
	->withRootFiles()
	// Targets the lowest PHP version in composer.json.
	->withPhpVersion(PhpVersion::PHP_81)
	->withPhpSets(php81: true)
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		typeDeclarations: true,
		earlyReturn: true,
	)
	->withSets([Typo3LevelSetList::UP_TO_TYPO3_12])
	->withImportNames(removeUnusedImports: true)
	->withParallel();
