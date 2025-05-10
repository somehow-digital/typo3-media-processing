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

try {
	$provider = GeneralUtility::makeInstance(ProviderFactory::class)();

	$collection = $provider?->hasConfiguration() ? [
		new Mutation(MutationMode::Extend, Directive::ImgSrc, new UriValue($provider?->getEndpoint())),
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
