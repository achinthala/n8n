<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Helper;

use Magento\Framework\App\RequestInterface;

class RequestContext
{
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    public function isLinkPrefetchRequest(): bool
    {
        foreach ($this->getPrefetchHeaderValues() as $value) {
            if ($value !== '' && (str_contains($value, 'prefetch') || str_contains($value, 'preview'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getPrefetchHeaderValues(): array
    {
        return [
            strtolower(trim((string) $this->request->getHeader('Purpose'))),
            strtolower(trim((string) $this->request->getHeader('X-Purpose'))),
            strtolower(trim((string) $this->request->getHeader('Sec-Purpose'))),
            strtolower(trim((string) $this->request->getHeader('X-Moz'))),
        ];
    }
}
