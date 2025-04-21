<?php

namespace SomehowDigital\Typo3\MediaProcessing\EventListener;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class ModifyClearCacheActionsEventListener
{
	public function __construct(
		private readonly ?ImageServiceInterface $service,
		private readonly ?UriBuilder $builder,
	) {
	}

	public function __invoke(ModifyClearCacheActionsEvent $event): void
	{
		if (
			$this->service?->hasConfiguration() &&
			$this->getBackendUser()->isAdmin()
		) {
			$event->addCacheAction([
				'id' => 'media',
				'title' => 'LLL:EXT:media_processing/Resources/Private/Language/backend.xlf:invalidation_title',
				'description' => 'LLL:EXT:media_processing/Resources/Private/Language/backend.xlf:invalidation_description',
				'href' => $this->builder?->buildUriFromRoute('ajax_media_processing_invalidation'),
				'iconIdentifier' => 'refresh',
			]);
		}
	}

	private function getBackendUser(): BackendUserAuthentication
	{
		return $GLOBALS['BE_USER'];
	}
}
