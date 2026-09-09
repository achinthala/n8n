<?php

declare(strict_types=1);

namespace Dcw\CacheWarmer\Console\Command;

use Dcw\CacheWarmer\Service\CacheWarmerService;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to generate warmerurls.txt file with all active configurable product URLs
 */
class GenerateWarmerUrlsCommand extends Command
{
    const OPTION_STORE = 'store';

    /**
     * @var CacheWarmerService
     */
    private $cacheWarmerService;

    /**
     * @var State
     */
    private $state;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CacheWarmerService $cacheWarmerService
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        CacheWarmerService $cacheWarmerService,
        State $state,
        StoreManagerInterface $storeManager,
        string $name = null
    ) {
        $this->cacheWarmerService = $cacheWarmerService;
        $this->state = $state;
        $this->storeManager = $storeManager;
        parent::__construct($name);
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('dcw:cache-warmer:generate-urls')
            ->setDescription('Generate warmerurls.txt file with all active configurable product URLs')
            ->addOption(
                self::OPTION_STORE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Store ID (defaults to default store)',
                null
            );
        
        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeIdOption = $input->getOption(self::OPTION_STORE);
        $storeId = $storeIdOption !== null ? (int)$storeIdOption : null;

        try {
            // Set application area to frontend
            $this->state->setAreaCode(Area::AREA_FRONTEND);
            
            if ($storeId !== null) {
                $this->storeManager->setCurrentStore($storeId);
                $output->writeln("<info>Using store ID: {$storeId}</info>");
            } else {
                $store = $this->storeManager->getStore();
                $storeId = (int)$store->getId();
                $output->writeln("<info>Using default store ID: {$storeId}</info>");
            }

            $output->writeln('<info>Generating warmerurls.txt file...</info>');
            $output->writeln('');

            // Generate the file
            $result = $this->cacheWarmerService->generateWarmerUrlsFile($storeId);

            $output->writeln('<info>✓ File generated successfully!</info>');
            $output->writeln("<comment>Total URLs: {$result['count']}</comment>");
            $output->writeln("<comment>File path: {$result['file_path']}</comment>");
            $output->writeln('');

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln('<error>Error generating warmerurls.txt: ' . $e->getMessage() . '</error>');
            $output->writeln('<comment>Stack trace:</comment>');
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
