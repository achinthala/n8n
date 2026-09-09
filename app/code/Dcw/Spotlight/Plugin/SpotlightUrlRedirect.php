<?php

namespace Dcw\Spotlight\Plugin;

class SpotlightUrlRedirect
{
    public function beforeSetRedirect(
        \Magento\Framework\App\Response\Http $subject,
        $url,
        $code = 302
    ) {
        // Only modify if query string exists
        if (!empty($_SERVER['QUERY_STRING'])) {
            $queryString = $_SERVER['QUERY_STRING'];

            // If redirect URL already has ?, append with &, otherwise with ?
            if (strpos($url, '?') === false) {
                $url .= '?' . $queryString;
            } else {
                $url .= '&' . $queryString;
            }
        }

        // Return modified arguments
        return [$url, $code];
    }
}
