<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Command;

use SomehowDigital\Typo3\MediaProcessing\ImageService\ImageServiceInterface;
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
		private readonly ?ImageServiceInterface $service,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setHelp('Invalidate processed media files.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if ($this->service?->hasConfiguration()) {
			$this->connectionPool
				->getConnectionForTable(static::TABLE_NAME)
				->delete(static::TABLE_NAME, [
					'integration' => $this->service::getIdentifier(),
				], [
					Connection::PARAM_STR,
				]);

			return Command::SUCCESS;
		}

		return Command::FAILURE;
	}
}
