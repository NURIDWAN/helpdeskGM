<?php

namespace App\Support;

class RichTextSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><a>';

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? '';
        $html = preg_replace('/\s+(style|class|id)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($matches) {
            if (!preg_match('/\shref\s*=\s*("|\')(.*?)\1/i', $matches[1], $hrefMatch)) {
                return '<a>';
            }

            $href = trim(html_entity_decode($hrefMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!preg_match('#^https?://#i', $href) && !str_starts_with($href, 'mailto:')) {
                return '<a>';
            }

            return '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer">';
        }, $html) ?? '';

        $html = trim($html);

        return $html === '' ? null : $html;
    }
}
