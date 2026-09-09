<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Config\Source;

use Dcw\AiContentCreator\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * AI provider options for system config.
 */
class Provider implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::PROVIDER_OPENAI, 'label' => __('OpenAI')],
            ['value' => Config::PROVIDER_GEMINI, 'label' => __('Google Gemini')],
        ];
    }
}
