<?php

declare(strict_types=1);

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\GeneralUtility;

try {
	$service = GeneralUtility::makeInstance(ImageServiceFactory::class)();

	$collection = $service?->hasConfiguration() ? [
		new Mutation(MutationMode::Extend, Directive::ImgSrc, SourceScheme::http, new UriValue($service?->getEndpoint())),
		new Mutation(MutationMode::Extend, Directive::ImgSrc, SourceScheme::https, new UriValue($service?->getEndpoint())),
	] : [];

	return Map::fromEntries([
		Scope::backend(),
		new MutationCollection(...$collection),
	]);
} catch (Throwable) {
}
