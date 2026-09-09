<?php
/**
 * Bazaarvoice FTP Import CLI Command
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Console\Command;

use Dcw\BazaarvoiceFtpImport\Model\ImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    /**
     * @var ImportService
     */
    private $importService;

    /**
     * ImportCommand constructor.
     *
     * @param ImportService $importService
     */
    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('bazaarvoice:ftp:import')
            ->setDescription('Import Bazaarvoice reviews from FTP server')
            ->addOption(
                'store-id',
                's',
                InputOption::VALUE_OPTIONAL,
                'Store ID to use for configuration',
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
        $storeId = $input->getOption('store-id');

        $output->writeln('<info>Starting Bazaarvoice FTP import...</info>');

        try {
            $result = $this->importService->execute($storeId);

            if ($result['success']) {
                $output->writeln('<info>Import completed successfully!</info>');
                $output->writeln(sprintf(
                    'Processed: %d files, Downloaded: %d files, Extracted: %d files',
                    $result['files_processed'],
                    $result['files_downloaded'],
                    $result['files_extracted']
                ));

                if (!empty($result['errors'])) {
                    $output->writeln('<comment>Warnings:</comment>');
                    foreach ($result['errors'] as $error) {
                        $output->writeln('<comment>  - ' . $error . '</comment>');
                    }
                }

                if (!empty($result['processed_files'])) {
                    $output->writeln('<info>Processed files:</info>');
                    foreach ($result['processed_files'] as $file) {
                        $status = [];
                        if ($file['downloaded']) $status[] = 'Downloaded';
                        if ($file['extracted']) $status[] = 'Extracted';
                        $output->writeln(sprintf('  - %s (%s)', $file['filename'], implode(', ', $status)));
                    }
                }

                return 0;
            } else {
                $output->writeln('<error>Import failed!</error>');
                foreach ($result['errors'] as $error) {
                    $output->writeln('<error>  - ' . $error . '</error>');
                }
                return 1;
            }

        } catch (\Exception $e) {
            $output->writeln('<error>Import failed: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
