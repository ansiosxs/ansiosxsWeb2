<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

final class RichTextSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'div', 'em', 'h2', 'h3', 'i', 'li', 'ol', 'p', 'strong', 'u', 'ul',
    ];

    private const REMOVE_WITH_CONTENT = ['iframe', 'object', 'script', 'style', 'svg', 'template'];

    public function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorSetting = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8"><div id="rich-text-root">'.$html.'</div>',
                LIBXML_HTML_NODEFDTD
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorSetting);
        }

        if (! $loaded) {
            throw new RuntimeException('No se pudo procesar el contenido HTML.');
        }

        $xpath = new DOMXPath($document);
        $root = $xpath->query('//*[@id="rich-text-root"]')?->item(0);

        if (! $root) {
            throw new RuntimeException('No se pudo procesar el contenido HTML.');
        }

        $this->sanitizeChildren($root, $document);

        $cleanHtml = '';
        foreach ($root->childNodes as $child) {
            $cleanHtml .= $document->saveHTML($child);
        }

        return $cleanHtml;
    }

    private function sanitizeChildren(DOMNode $parent, DOMDocument $document): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->sanitizeChildren($child, $document);

                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }

                $parent->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? $this->safeHref($child->getAttribute('href')) : null;

            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }

            if ($tag === 'a') {
                if ($href === null) {
                    $this->sanitizeChildren($child, $document);

                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }

                    $parent->removeChild($child);

                    continue;
                }

                $child->setAttribute('href', $href);
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener noreferrer');
            }

            $this->sanitizeChildren($child, $document);
        }
    }

    private function safeHref(string $href): ?string
    {
        $href = trim(preg_replace('/[\x00-\x20\x7f]+/', '', $href) ?? '');

        if ($href === '' || strlen($href) > 2048 || str_starts_with($href, '//')) {
            return null;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);

        if ($scheme !== null && (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https', 'mailto'], true))) {
            return null;
        }

        return $href;
    }
}
