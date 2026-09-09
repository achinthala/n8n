<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Cron;

use Dcw\ShareCart\Service\LinkLifecycle;
use Psr\Log\LoggerInterface;

class ExpireLinks
{
    public function __construct(
        private readonly LinkLifecycle $linkLifecycle,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $count = $this->linkLifecycle->expireOutdatedLinks();
        if ($count > 0) {
            $this->logger->info(sprintf('Dcw_ShareCart: expired %d share cart link(s).', $count));
        }
    }
}
