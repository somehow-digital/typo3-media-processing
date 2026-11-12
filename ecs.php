<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
	->withPaths([
		__DIR__ . '/Classes',
		__DIR__ . '/Configuration',
		__DIR__ . '/Tests',
	])
	->withRootFiles()
	->withPreparedSets(
		perCs: true,
		arrays: true,
		comments: true,
		docblocks: true,
		spaces: true,
		namespaces: true,
		controlStructures: true,
		casing: true,
		cleanup: true,
		cleanCode: true,
	)
	->withSkip([
		// PER-CS has no space after `!`.
		NotOperatorWithSuccessorSpaceFixer::class,
	])
	->withSpacing(Option::INDENTATION_TAB, "\n")
	->withParallel();
