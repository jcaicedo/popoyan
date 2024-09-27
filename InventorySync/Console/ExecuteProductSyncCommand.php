<?php
declare(strict_types=1);

namespace Popoyan\InventorySync\Console;

use Popoyan\InventorySync\Cron\SyncProducts;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteProductSyncCommand extends Command
{
    public function __construct(
        private SyncProducts    $syncProducts,
        private LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('popoyan-inventory-sync:execute');
        $this->setDescription('Execute product sync command');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Updating inventory...</info>');

        try {
            $this->syncProducts->execute();
            $output->writeln('<info>Inventory sync completed successfully.</info>');

        } catch (\Exception $exception) {
            $this->logger->error('Error during inventory sync: ' . $exception->getMessage());
            $output->writeln('<error>Error during inventory sync: ' . $exception->getMessage() . '</error>');
        }

        return 0;
    }
}
