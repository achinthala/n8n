<?php
/**
 * GMC Convert CLI Command
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Console\Command;

use Dcw\BazaarvoiceFtpImport\Model\GmcReviewConverter;
use Dcw\BazaarvoiceFtpImport\Model\FileProcessor;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class GmcConvertCommand extends Command
{
    const STORE_ID_OPTION = 'store-id';

    /**
     * @var State
     */
    private $state;

    /**
     * @var GmcReviewConverter
     */
    private $gmcConverter;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GmcConvertCommand constructor.
     *
     * @param State $state
     * @param GmcReviewConverter $gmcConverter
     * @param FileProcessor $fileProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        State $state,
        GmcReviewConverter $gmcConverter,
        FileProcessor $fileProcessor,
        LoggerInterface $logger
    ) {
        $this->state = $state;
        $this->gmcConverter = $gmcConverter;
        $this->fileProcessor = $fileProcessor;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('bazaarvoice:gmc:convert');
        $this->setDescription('Convert Bazaarvoice XML files to Google Merchant Center format');
        $this->addOption(
            self::STORE_ID_OPTION,
            's',
            InputOption::VALUE_OPTIONAL,
            'Store ID for configuration scope',
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            // Area code already set
        }

        $storeId = $input->getOption(self::STORE_ID_OPTION);
        $output->writeln('<info>Starting GMC conversion process...</info>');

        try {
            // Check if GMC conversion is enabled
            if (!$this->gmcConverter->isEnabled($storeId)) {
                $output->writeln('<error>GMC conversion is not enabled. Please enable it in the configuration.</error>');
                return 1;
            }

            // Get import directory
            $importPath = $this->fileProcessor->getAbsoluteLocalPath($storeId);
            $extractPath = $importPath . '/extracted/';

            $filesProcessed = 0;
            $gmcFilesGenerated = 0;
            $errors = [];

            // Process XML files in the extracted directory
            if (!is_dir($extractPath)) {
                $output->writeln('<error>Extracted directory not found: ' . $extractPath . '</error>');
                $output->writeln('<comment>Please run the import first to extract XML files.</comment>');
                return 1;
            }

            $xmlFiles = glob($extractPath . '*.xml');

            if (empty($xmlFiles)) {
                $output->writeln('<error>No XML files found in the extracted directory.</error>');
                $output->writeln('<comment>Please run the import first to extract XML files.</comment>');
                return 1;
            }

            $output->writeln('<info>Found ' . count($xmlFiles) . ' XML file(s) to process...</info>');

            foreach ($xmlFiles as $xmlFile) {
                try {
                    $output->writeln('<info>Converting: ' . basename($xmlFile) . '</info>');
                    $this->logger->info('CLI GMC conversion: Converting ' . basename($xmlFile));

                    $gmcFile = $this->gmcConverter->convertToGmc($xmlFile, $storeId);

                    if ($gmcFile) {
                        $gmcFilesGenerated++;
                        $output->writeln('<info>Generated GMC file: ' . basename($gmcFile) . '</info>');
                        $this->logger->info('CLI GMC conversion: Generated ' . basename($gmcFile));
                    }

                    $filesProcessed++;

                } catch (\Exception $e) {
                    $error = 'Failed to convert ' . basename($xmlFile) . ': ' . $e->getMessage();
                    $output->writeln('<error>' . $error . '</error>');
                    $this->logger->error('CLI GMC conversion error: ' . $error);
                    $errors[] = $error;
                }
            }

            // Get output directory for display
            $outputDir = $this->gmcConverter->getOutputDirectory($storeId);

            $output->writeln('');
            $output->writeln('<info>GMC conversion completed!</info>');
            $output->writeln('<info>Files processed: ' . $filesProcessed . '</info>');
            $output->writeln('<info>GMC files generated: ' . $gmcFilesGenerated . '</info>');
            $output->writeln('<info>Output directory: ' . $outputDir . '</info>');

            if (!empty($errors)) {
                $output->writeln('<error>Errors encountered:</error>');
                foreach ($errors as $error) {
                    $output->writeln('<error>- ' . $error . '</error>');
                }
                return 1;
            }

            $this->logger->info("CLI GMC conversion completed. Files processed: {$filesProcessed}, GMC files generated: {$gmcFilesGenerated}");
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>GMC conversion failed: ' . $e->getMessage() . '</error>');
            $this->logger->error('CLI GMC conversion failed: ' . $e->getMessage());
            return 1;
        }
    }
}
