<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class InvalidationCommand extends Command
{
	private const TABLE_NAME = 'sys_file_processedfile';

	public function __construct(
		private readonly ConnectionPool $connectionPool,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setHelp('Invalidate processed media files.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$builder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

		$builder
			->delete(self::TABLE_NAME)
			->where($builder->expr()->neq('integration', $builder->createNamedParameter('', Connection::PARAM_STR)))
			->executeStatement();

		return Command::SUCCESS;
	}
}
