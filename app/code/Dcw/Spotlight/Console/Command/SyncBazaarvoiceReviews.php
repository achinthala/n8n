<?php
/**
 * Sync Bazaarvoice Reviews Console Command
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dcw\Spotlight\Service\BazaarvoiceReviewParser;
use Dcw\Spotlight\Service\ReviewDataSaver;
use Magento\Framework\App\State;

/**
 * Class SyncBazaarvoiceReviews
 */
class SyncBazaarvoiceReviews extends Command
{
    const OPTION_CLEAR = 'clear';

    /**
     * @var BazaarvoiceReviewParser
     */
    private $reviewParser;

    /**
     * @var ReviewDataSaver
     */
    private $dataSaver;

    /**
     * @var State
     */
    private $state;

    /**
     * Constructor
     *
     * @param BazaarvoiceReviewParser $reviewParser
     * @param ReviewDataSaver $dataSaver
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        BazaarvoiceReviewParser $reviewParser,
        ReviewDataSaver $dataSaver,
        State $state,
        string $name = null
    ) {
        $this->reviewParser = $reviewParser;
        $this->dataSaver = $dataSaver;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('dcw:spotlight:sync-reviews')
            ->setDescription('Sync Bazaarvoice reviews to database tables')
            ->addOption(
                self::OPTION_CLEAR,
                null,
                InputOption::VALUE_NONE,
                'Clear all existing review data before syncing'
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
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area code already set
        }

        $output->writeln('<info>Starting Bazaarvoice Reviews Sync...</info>');
        
        try {
            // Clear data if requested
            if ($input->getOption(self::OPTION_CLEAR)) {
                $output->writeln('<comment>Clearing existing data...</comment>');
                $this->dataSaver->clearAllData();
                $output->writeln('<info>Data cleared successfully.</info>');
            }

            // Check if file exists
            $filePath = $this->reviewParser->getFilePath();
            if (!file_exists($filePath)) {
                $output->writeln("<error>XML file not found at: {$filePath}</error>");
                return Command::FAILURE;
            }

            $output->writeln("<comment>Parsing XML file: {$filePath}</comment>");
            
            // Parse reviews
            $startTime = microtime(true);
            $groupedData = $this->reviewParser->parseReviews();
            $parseTime = round(microtime(true) - $startTime, 2);

            $totalProducts = count($groupedData);
            $totalReviews = 0;
            foreach ($groupedData as $data) {
                $totalReviews += $data['total_reviews'];
            }

            $output->writeln("<info>Parsing completed in {$parseTime} seconds</info>");
            $output->writeln("<info>Found {$totalProducts} products with {$totalReviews} approved reviews</info>");

            // Save to database
            $output->writeln('<comment>Saving data to database...</comment>');
            $startTime = microtime(true);
            $stats = $this->dataSaver->saveData($groupedData);
            $saveTime = round(microtime(true) - $startTime, 2);

            $output->writeln("<info>Save completed in {$saveTime} seconds</info>");
            $output->writeln('');
            $output->writeln('<info>Results:</info>');
            $output->writeln("  - Product ratings created: {$stats['ratings_saved']}");
            $output->writeln("  - Product ratings updated: {$stats['ratings_updated']}");
            $output->writeln("  - Top reviews saved: {$stats['reviews_saved']}");
            
            if ($stats['errors'] > 0) {
                $output->writeln("<comment>  - Errors encountered: {$stats['errors']}</comment>");
            }

            $output->writeln('');
            $output->writeln('<info>Sync completed successfully!</info>');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            $output->writeln('<error>Stack trace: ' . $e->getTraceAsString() . '</error>');
            return Command::FAILURE;
        }
    }
}

