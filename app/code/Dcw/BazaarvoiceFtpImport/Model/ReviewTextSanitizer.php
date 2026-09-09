<?php

/**
 * Sanitize review title and body before GMC export (URLs, sales agent names, reviewer self-reference).
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 */

namespace Dcw\BazaarvoiceFtpImport\Model;

class ReviewTextSanitizer
{
    /**
     * Sales agents as [firstName, lastName] per internal list format.
     */
    private const SALES_AGENTS = [
        ['Conyette', 'Sean'],
        ['Ferguson', 'Justin'],
        ['Beberniss', 'Tyler'],
        ['Conyette', 'Natasia'],
        ['Hamilton', 'Marquel'],
        ['Morgan', 'Sabrina'],
        ['Cabuenas', 'Reggie'],
        ['Ruiz', 'Elly'],
        ['Vitte', 'Alicia M'],
        ['Empleo', 'Christine'],
        ['Bayne', 'Shawn'],
        ['Alvarado', 'Angela'],
        ['Moreno', 'Teanna'],
        ['Pimental', 'Lee'],
        ['Ramos', 'Alexia'],
        ['Villanueva', 'Jessica'],
        ['Tolley', 'Stephanie'],
        ['Vicencio', 'Hazel'],
        ['Austria', 'Jermaine'],
        ['Carrasco', 'Judith'],
        ['Ramores', 'Em'],
    ];

    /**
     * Decode HTML entities, strip URLs, remove reviewer self-reference, replace sales agent names with CS.
     */
    public function sanitizeReviewText(string $text, string $reviewerName = ''): string
    {
        $text = $this->decodeHtmlEntities($text);

        if ($reviewerName !== '') {
            $reviewerName = $this->decodeHtmlEntities($reviewerName);
        }

        $text = $this->stripUrls($text);

        if ($reviewerName !== '') {
            $text = $this->replaceReviewerSelfReference($text, $reviewerName);
        }

        return $this->replaceSalesAgentNames($text);
    }

    /**
     * Convert HTML entities (e.g. &#39;, &amp;, &quot;) to their actual characters.
     */
    public function decodeHtmlEntities(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $previous = null;
        while ($previous !== $text) {
            $previous = $text;
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $text;
    }

    /**
     * Remove http(s), www, and bare domain/path URLs from review text.
     */
    public function stripUrls(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $patterns = [
            '#\bhttps?://[^\s<>"\'\]\)]+#iu',
            '#\bwww\.[^\s<>"\'\]\)]+#iu',
            '#\b[a-z0-9](?:[-a-z0-9]*[a-z0-9])?(?:\.[a-z0-9](?:[-a-z0-9]*[a-z0-9])?)+(?:/[^\s<>"\'\]\)]*)?#iu',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Replace sales agent first/last/full names with CS.
     */
    public function replaceSalesAgentNames(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $patterns = $this->buildSalesAgentPatterns();

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, 'CS', $text) ?? $text;
        }

        $text = preg_replace('/\bCS(?:\s+CS)+\b/iu', 'CS', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Remove the reviewer's own full, first, or last name from title/body text.
     */
    public function replaceReviewerSelfReference(string $text, string $reviewerName): string
    {
        if ($text === '' || trim($reviewerName) === '' || strcasecmp(trim($reviewerName), 'Anonymous') === 0) {
            return $text;
        }

        $patterns = $this->buildReviewerSelfReferencePatterns(trim($reviewerName));

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        $text = preg_replace("/(?:^|\s)'s\b/u", ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array{full: string, first: string, last: string, parts: string[]}
     */
    private function parseReviewerNameParts(string $reviewerName): array
    {
        $full = preg_replace('/\s+/', ' ', trim($reviewerName)) ?? trim($reviewerName);
        if ($full === '') {
            return ['full' => '', 'first' => '', 'last' => '', 'parts' => []];
        }

        $parts = preg_split('/[\s,&]+/', $full, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_filter(
            $parts,
            static fn (string $part): bool => $part !== '.' && $part !== '-'
        ));

        return [
            'full' => $full,
            'first' => $parts[0] ?? '',
            'last' => count($parts) > 1 ? $parts[count($parts) - 1] : '',
            'parts' => $parts,
        ];
    }

    /**
     * @return string[] Regex patterns ordered longest match first
     */
    private function buildReviewerSelfReferencePatterns(string $reviewerName): array
    {
        $parsed = $this->parseReviewerNameParts($reviewerName);
        if ($parsed['full'] === '') {
            return [];
        }

        $patterns = [];
        $fullQ = preg_quote($parsed['full'], '/');
        $patterns[] = $this->reviewerNamePattern($fullQ . '\'s');
        $patterns[] = $this->reviewerNamePattern($fullQ);

        if (count($parsed['parts']) >= 2) {
            $firstQ = preg_quote($parsed['first'], '/');
            $lastQ = preg_quote($parsed['last'], '/');

            $patterns[] = $this->reviewerNamePattern($firstQ . '\s+' . $lastQ . '\'s');
            $patterns[] = $this->reviewerNamePattern($firstQ . '\s+' . $lastQ);
            $patterns[] = $this->reviewerNamePattern($lastQ . '\s+' . $firstQ . '\'s');
            $patterns[] = $this->reviewerNamePattern($lastQ . '\s+' . $firstQ);
            $patterns[] = $this->reviewerNamePattern($firstQ . ',\s*' . $lastQ . '\'s');
            $patterns[] = $this->reviewerNamePattern($firstQ . ',\s*' . $lastQ);
            $patterns[] = $this->reviewerNamePattern($lastQ . ',\s*' . $firstQ . '\'s');
            $patterns[] = $this->reviewerNamePattern($lastQ . ',\s*' . $firstQ);
        }

        $first = $parsed['first'];
        if ($first !== '' && mb_strlen($first) >= 2) {
            $firstQ = preg_quote($first, '/');
            $patterns[] = $this->reviewerNamePattern($firstQ . '\'s');
            $patterns[] = $this->reviewerNamePattern($firstQ);
        }

        $last = $parsed['last'];
        $lastStem = rtrim($last, '.');
        if (
            $last !== ''
            && strcasecmp($last, $first) !== 0
            && mb_strlen($lastStem) >= 2
        ) {
            $lastBase = rtrim($last, '.');
            if ($lastBase !== $last && $lastBase !== '') {
                $patterns[] = $this->reviewerNamePattern(preg_quote($lastBase, '/') . '\.?\'s');
                $patterns[] = $this->reviewerNamePattern(preg_quote($lastBase, '/') . '\.?');
            } else {
                $lastQ = preg_quote($last, '/');
                $patterns[] = $this->reviewerNamePattern($lastQ . '\'s');
                $patterns[] = $this->reviewerNamePattern($lastQ);
            }
        }

        usort($patterns, static function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });

        return array_values(array_unique($patterns));
    }

    private function reviewerNamePattern(string $quotedFragment): string
    {
        return '/(?<![\w])' . $quotedFragment . '(?![\w])/iu';
    }

    /**
     * @return string[] Regex patterns ordered longest match first
     */
    private function buildSalesAgentPatterns(): array
    {
        $patterns = [];

        foreach (self::SALES_AGENTS as [$first, $last]) {
            $firstQ = preg_quote($first, '/');
            $lastQ = preg_quote($last, '/');
            $lastParts = preg_split('/\s+/', trim($last));
            $lastPartsQ = array_map(static fn ($p) => preg_quote($p, '/'), $lastParts);

            $variants = [
                $firstQ . ',\s*' . $lastQ,
                $lastQ . ',\s*' . $firstQ,
                $firstQ . '\s+' . $lastQ,
                $lastQ . '\s+' . $firstQ,
            ];

            if (count($lastPartsQ) > 1) {
                $variants[] = $firstQ . '\s+' . $lastPartsQ[0] . '(?:\s+' . $lastPartsQ[1] . ')?';
                $variants[] = $lastPartsQ[0] . '\s+' . $firstQ;
            }

            foreach ($variants as $variant) {
                $patterns[] = '/\b' . $variant . '\b/iu';
            }

            $patterns[] = '/\b' . $firstQ . '\b/iu';
            foreach ($lastPartsQ as $partQ) {
                $patterns[] = '/\b' . $partQ . '\b/iu';
            }
        }

        usort($patterns, static function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });

        return array_values(array_unique($patterns));
    }
}
