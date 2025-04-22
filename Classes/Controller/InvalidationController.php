<?php

namespace SomehowDigital\Typo3\MediaProcessing\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;

class InvalidationController
{
	private const TABLE_NAME = 'sys_file_processedfile';

	public function __construct(
		private readonly ConnectionPool $connectionPool,
	) {
	}

	public function __invoke(): ResponseInterface
	{
		if ($this->getBackendUser()->isAdmin()) {
			$builder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

			$builder
				->delete(self::TABLE_NAME)
				->where($builder->expr()->neq('integration', $builder->createNamedParameter('', Connection::PARAM_STR)))
				->executeStatement();

			return new JsonResponse();
		}

		return new JsonResponse(null, 403);
	}

	private function getBackendUser(): BackendUserAuthentication
	{
		return $GLOBALS['BE_USER'];
	}
}
