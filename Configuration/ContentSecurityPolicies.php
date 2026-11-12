<?php

declare(strict_types=1);

use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

try {
	$provider = GeneralUtility::makeInstance(ProviderFactory::class)();
    $endpoint = $provider?->getEndpoint();

	$isAbsolute = $endpoint !== null && filter_var($endpoint, FILTER_VALIDATE_URL) !== false;

	$collection = ($provider?->hasConfiguration() && $isAbsolute) ? [
		new Mutation(MutationMode::Extend, Directive::ImgSrc, new UriValue($endpoint)),
	] : [];


	return Map::fromEntries([
		Scope::backend(),
		new MutationCollection(...$collection),
	], [
		Scope::frontend(),
		new MutationCollection(...$collection),
	]);
} catch (Throwable) {
}
