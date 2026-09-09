<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Dcw\Spotlight\Model\JsonLd\ProductInfo;
use Dcw\Spotlight\Model\Cache\JsonLdCache;

/**
 * CLI command to generate JSON-LD for a single product
 */
class GenerateJsonLdCommand extends Command
{
    const ARGUMENT_PRODUCT = 'product';
    const OPTION_BY_SKU = 'sku';
    const OPTION_STORE = 'store';
    const OPTION_NO_CACHE = 'no-cache';
    const OPTION_SAVE_FILE = 'save';
    
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var ProductInfo
     */
    private $productInfo;
    
    /**
     * @var JsonLdCache
     */
    private $jsonLdCache;
    
    /**
     * @var State
     */
    private $state;
    
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductInfo $productInfo
     * @param JsonLdCache $jsonLdCache
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ProductInfo $productInfo,
        JsonLdCache $jsonLdCache,
        State $state,
        string $name = null
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->productInfo = $productInfo;
        $this->jsonLdCache = $jsonLdCache;
        $this->state = $state;
        parent::__construct($name);
    }
    
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('dcw:spotlight:jsonld:generate')
            ->setDescription('Generate JSON-LD schema for a single product')
            ->addArgument(
                self::ARGUMENT_PRODUCT,
                InputArgument::REQUIRED,
                'Product ID or SKU'
            )
            ->addOption(
                self::OPTION_BY_SKU,
                's',
                InputOption::VALUE_NONE,
                'Search by SKU instead of ID'
            )
            ->addOption(
                self::OPTION_STORE,
                null,
                InputOption::VALUE_REQUIRED,
                'Store ID',
                1
            )
            ->addOption(
                self::OPTION_NO_CACHE,
                null,
                InputOption::VALUE_NONE,
                'Skip cache and generate fresh data'
            )
            ->addOption(
                self::OPTION_SAVE_FILE,
                null,
                InputOption::VALUE_REQUIRED,
                'Save output to file (provide filename)'
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
        $productIdentifier = $input->getArgument(self::ARGUMENT_PRODUCT);
        $isBySku = $input->getOption(self::OPTION_BY_SKU);
        $storeId = (int)$input->getOption(self::OPTION_STORE);
        $noCache = $input->getOption(self::OPTION_NO_CACHE);
        $saveFile = $input->getOption(self::OPTION_SAVE_FILE);
        
        try {
            // Set application area to frontend
            $this->state->setAreaCode(Area::AREA_FRONTEND);
            
            // Set store
            $this->storeManager->setCurrentStore($storeId);
            
            $output->writeln('<info>Loading product...</info>');
            
            // Load product
            if ($isBySku) {
                $product = $this->productRepository->get($productIdentifier, false, $storeId);
                $output->writeln("<comment>Product loaded by SKU: {$productIdentifier}</comment>");
            } else {
                $product = $this->productRepository->getById((int)$productIdentifier, false, $storeId);
                $output->writeln("<comment>Product loaded by ID: {$productIdentifier}</comment>");
            }
            
            $output->writeln("<info>Product Name: {$product->getName()}</info>");
            $output->writeln("<info>Product Type: {$product->getTypeId()}</info>");
            $output->writeln("<info>Product SKU: {$product->getSku()}</info>");
            $output->writeln('');
            
            // Clear cache if requested
            if ($noCache) {
                $this->jsonLdCache->clearProduct((int)$product->getId());
                $output->writeln('<comment>Cache cleared for this product.</comment>');
            }
            
            // Generate JSON-LD
            $output->writeln('<info>Generating JSON-LD schema...</info>');
            $jsonLdData = $this->productInfo->extract($product);
            
            // Check if configurable and show variant count
            if ($product->getTypeId() === 'configurable' && isset($jsonLdData['hasVariant'])) {
                $variantCount = count($jsonLdData['hasVariant']);
                $output->writeln("<info>Found {$variantCount} variants</info>");
            }
            
            // Format JSON with proper encoding flags
            $jsonOutput = json_encode(
                $jsonLdData,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            
            // Check for JSON encoding errors
            if ($jsonOutput === false) {
                $output->writeln('<error>JSON encoding error: ' . json_last_error_msg() . '</error>');
                return Command::FAILURE;
            }
            
            // Save to file if requested
            if ($saveFile) {
                $filePath = BP . '/' . $saveFile;
                file_put_contents($filePath, $jsonOutput);
                $output->writeln("<info>JSON-LD saved to: {$filePath}</info>");
            }
            
            // Display output
            $output->writeln('');
            $output->writeln('<comment>==================== JSON-LD OUTPUT ====================</comment>');
            $output->writeln($jsonOutput);
            $output->writeln('<comment>=========================================================</comment>');
            $output->writeln('');
            
            // Show statistics
            $output->writeln('<info>Statistics:</info>');
            $output->writeln("  - Total JSON size: " . strlen($jsonOutput) . " bytes");
            $output->writeln("  - Schema Type: " . ($jsonLdData['@type'] ?? 'Unknown'));
            if (isset($jsonLdData['hasVariant'])) {
                $output->writeln("  - Total Variants: " . count($jsonLdData['hasVariant']));
            }
            $output->writeln('');
            
            $output->writeln('<info>✓ JSON-LD generated successfully!</info>');
            
            return Command::SUCCESS;
            
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $output->writeln('<error>Product not found: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('<error>Error generating JSON-LD: ' . $e->getMessage() . '</error>');
            $output->writeln('<comment>Stack trace:</comment>');
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

