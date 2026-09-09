<?php
/**
 * Plugin to add Get New Quote button to admin quote view page for expired quotes
 */

declare(strict_types=1);

namespace Dcw\RequestQuote\Plugin\Adminhtml\Quote\View;

use Amasty\RequestQuote\Block\Adminhtml\Quote\View;
use Amasty\RequestQuote\Model\Source\Status;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;

/**
 * Plugin to add Get New Quote button to Amasty Quote View toolbar for expired quotes
 */
class AddGetNewQuoteButton
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Escaper $escaper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Escaper $escaper,
        UrlInterface $urlBuilder
    ) {
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Add Get New Quote button before layout is set
     *
     * @param View $subject
     * @return void
     */
    public function beforeSetLayout(View $subject): void
    {
        $quote = $subject->getQuote();

        // Check if we have a valid quote
        if (!$quote || !$quote->getId()) {
            return;
        }

        // Only show button for expired quotes
        if ($quote->getStatus() != Status::EXPIRED) {
            return;
        }

        $quoteId = (int)$quote->getId();
        $createUrl = $this->urlBuilder->getUrl(
            'dcwrequestquote/quote/createNewFromExpired',
            ['quote_id' => $quoteId]
        );

        // Add the Get New Quote button
        $subject->addButton(
            'get_new_quote',
            [
                'label' => __('Get New Quote'),
                'onclick' => 'setLocation("' . $this->escaper->escapeUrl($createUrl) . '")',
                'class' => 'get-new-quote',
            ],
            1 // Position after default buttons
        );
    }
}

