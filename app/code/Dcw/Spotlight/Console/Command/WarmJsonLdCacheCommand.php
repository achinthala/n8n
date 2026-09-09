<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dcw\Spotlight\Cron\WarmJsonLdCache;
use Dcw\Spotlight\Model\Cache\JsonLdCache;

/**
 * CLI command to warm JSON-LD cache
 */
class WarmJsonLdCacheCommand extends Command
{
    const OPTION_CLEAR = 'clear';
    
    /**
     * @var WarmJsonLdCache
     */
    private $warmCache;
    
    /**
     * @var JsonLdCache
     */
    private $jsonLdCache;
    
    /**
     * @param WarmJsonLdCache $warmCache
     * @param JsonLdCache $jsonLdCache
     * @param string|null $name
     */
    public function __construct(
        WarmJsonLdCache $warmCache,
        JsonLdCache $jsonLdCache,
        string $name = null
    ) {
        $this->warmCache = $warmCache;
        $this->jsonLdCache = $jsonLdCache;
        parent::__construct($name);
    }
    
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('dcw:spotlight:jsonld:warm')
            ->setDescription('Warm JSON-LD schema cache for all configurable products')
            ->addOption(
                self::OPTION_CLEAR,
                'c',
                InputOption::VALUE_NONE,
                'Clear existing cache before warming'
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
        $output->writeln('<info>Starting JSON-LD cache warming process...</info>');
        
        try {
            // Clear cache if option is set
            if ($input->getOption(self::OPTION_CLEAR)) {
                $output->writeln('<comment>Clearing existing JSON-LD cache...</comment>');
                $this->jsonLdCache->clearAll();
                $output->writeln('<info>Cache cleared successfully.</info>');
            }
            
            // Warm cache
            $output->writeln('<comment>Generating and caching JSON-LD data for all configurable products...</comment>');
            $this->warmCache->execute();
            
            $output->writeln('<info>JSON-LD cache warming completed successfully!</info>');
            $output->writeln('<comment>Check var/log/system.log for detailed information.</comment>');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error warming JSON-LD cache: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}


