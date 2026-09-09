<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Sanitizer;

/**
 * Strips unsafe HTML from AI-generated description fields before Apply Draft.
 */
class HtmlSanitizer
{
    /**
     * Remove script/style/iframe and inline event handlers from generated HTML.
     */
    public function sanitize(string $content, string $attributeCode): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        // Meta fields should be plain text.
        if (in_array($attributeCode, ['meta_title', 'meta_description'], true)) {
            return trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // Strip dangerous tags.
        $content = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $content) ?? $content;
        $content = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta)[^>]*/?\s*>#is', '', $content) ?? $content;

        // Strip inline event handlers (onclick, onerror, etc.).
        $content = preg_replace('/\son[a-z]+\s*=\s*(["\']).*?\1/iu', '', $content) ?? $content;
        $content = preg_replace('/\son[a-z]+\s*=\s*[^\s>]+/iu', '', $content) ?? $content;

        // Neutralize javascript: URLs.
        $content = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/iu', '$1="#"', $content) ?? $content;

        return trim($content);
    }
}
