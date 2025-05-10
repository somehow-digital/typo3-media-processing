<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Report\Status;

use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

class ServiceStatus implements StatusProviderInterface
{
	private readonly ?LanguageService $translator;
	private readonly ?array $configuration;

	public function __construct(
		private readonly ?ProviderInterface $provider,
		?ExtensionConfiguration $configuration,
	) {
		$this->configuration = $configuration?->get('media_processing');
		$this->translator = $GLOBALS['LANG'];
	}

	public function getLabel(): string
	{
		return 'LLL:EXT:media_processing/Resources/Private/Language/report.xlf:label';
	}

	public function getStatus(): array
	{
		$result = [
			'provider' => $this->getProviderStatus(),
		];

		if ($this->provider) {
			$result['configuration'] = $this->getConfigurationStatus();
			$result['frontend'] = $this->getFrontendStatus();
			$result['backend'] = $this->getBackendStatus();
			$result['storage'] = $this->getStorageStatus();
		}

		return $result;
	}

	private function getProviderStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:provider'),
			$this->configuration['common']['provider'] ?: 'local',
			$this->provider?->getEndpoint(),
			ContextualFeedbackSeverity::INFO,
		);
	}

	private function getConfigurationStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:configuration'),
			$this->provider?->hasConfiguration() ? 'valid' : 'invalid',
			'',
			$this->provider?->hasConfiguration() ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
		);
	}

	private function getFrontendStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:frontend'),
			$this->configuration['common']['frontend'] ? 'enabled' : 'disabled',
			'',
			$this->configuration['common']['frontend'] ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::WARNING,
		);
	}

	private function getBackendStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:backend'),
			$this->configuration['common']['backend'] ? 'enabled' : 'disabled',
			'',
			$this->configuration['common']['backend'] ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::WARNING,
		);
	}

	private function getStorageStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:storage'),
			$this->configuration['common']['storage'] ? 'enabled' : 'disabled',
			'',
			$this->configuration['common']['storage'] ? ContextualFeedbackSeverity::WARNING : ContextualFeedbackSeverity::OK,
		);
	}
}
