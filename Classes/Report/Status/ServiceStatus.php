<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Report\Status;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

class ServiceStatus implements StatusProviderInterface
{
	private ?ImageServiceInterface $service;
	private ?array $configuration;
	private ?LanguageService $translator;

	public function __construct(
		?ImageServiceInterface $service,
		?ExtensionConfiguration $configuration,
	) {
		$this->service = $service;
		$this->configuration = $configuration?->get('media_processing');
		$this->translator = $GLOBALS['LANG'];
	}

	public function getLabel(): string {
		return 'image_processing';
	}

	public function getStatus(): array {
		$result = [
			'integration' => $this->getIntegrationStatus(),
		];

		if ($this->service) {
			$result['configuration'] = $this->getConfigurationStatus();
			$result['frontend'] = $this->getFrontendStatus();
			$result['backend'] = $this->getBackendStatus();
			$result['storage'] = $this->getStorageStatus();
		}

		return $result;
	}

	private function getIntegrationStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:integration'),
			$this->configuration['common']['integration'] ?: 'local',
			$this->service?->getEndpoint(),
			ContextualFeedbackSeverity::INFO,
		);
	}

	private function getConfigurationStatus(): Status
	{
		return GeneralUtility::makeInstance(
			Status::class,
			$this->translator->sL('LLL:EXT:media_processing/Resources/Private/Language/report.xlf:configuration'),
			$this->service?->hasConfiguration() ? 'valid' : 'invalid',
			'',
			$this->service?->hasConfiguration() ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
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
