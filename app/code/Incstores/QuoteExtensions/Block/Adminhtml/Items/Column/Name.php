<?php
declare(strict_types=1);

/**
 * Copyright © Incstores. All rights reserved.
 */

namespace Incstores\QuoteExtensions\Block\Adminhtml\Items\Column;

use Amasty\RequestQuote\Block\Adminhtml\Items\Column\Name as AmastyName;

/**
 * Extended Name column block to add variant and color display names
 */
class Name extends AmastyName
{
    /**
     * Get template file
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'Incstores_QuoteExtensions::items/column/name.phtml';
    }
}
