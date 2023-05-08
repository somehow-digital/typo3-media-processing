<?php

namespace SomehowDigital\Typo3\MediaProcessing\Controller;

use Psr\Http\Message\ResponseInterface;
use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;

class InvalidationController
{
	public function __construct(
		private readonly ConnectionPool $connectionPool,
		private readonly ?ImageServiceInterface $service,
	) {
	}

	public function __invoke(): ResponseInterface
	{
		if (
			$this->service?->hasConfiguration() &&
			$this->getBackendUser()->isAdmin()) {
			$this->connectionPool
				->getConnectionForTable('sys_file_processedfile')
				->delete('sys_file_processedfile', [
					'integration' => $this->service::getIdentifier(),
				], [
					Connection::PARAM_STR,
				]);

			return new JsonResponse();
		}

		return new JsonResponse(null, 403);
	}

	private function getBackendUser(): BackendUserAuthentication
	{
		return $GLOBALS['BE_USER'];
	}
}
