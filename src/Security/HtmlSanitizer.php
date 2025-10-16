<?php

declare(strict_types=1);

namespace Trees\Security;

class HtmlSanitizer
{
    private array $defaultAllowedTags = [
        'p', 'br', 'strong', 'em', 'u', 'i', 'b',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'a', 'img', 'blockquote', 'code', 'pre', 'span', 'div'
    ];

    public function sanitizeBasic(string $html, ?array $allowedTags = null): string
    {
        if ($allowedTags === null) {
            $allowedTags = $this->defaultAllowedTags;
        }
        
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        $sanitized = strip_tags($html, $allowedTagsString);
        
        return trim($sanitized);
    }

    public function sanitizeWithHtmlPurifier(string $html, ?\HTMLPurifier_Config $config = null): string
    {
        if (!class_exists('HTMLPurifier')) {
            throw new \RuntimeException('HTMLPurifier is required');
        }

        if ($config === null) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', implode(',', $this->defaultAllowedTags));
            $config->set('AutoFormat.RemoveEmpty', true);
        }

        $purifier = new \HTMLPurifier($config);
        return $purifier->purify(trim($html));
    }

    public function validateHtml(string $html, int $maxLength = 50000): void
    {
        if (strlen($html) > $maxLength) {
            throw new \InvalidArgumentException("HTML content too long");
        }

        // Check for potentially dangerous patterns
        $dangerousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $html)) {
                throw new \InvalidArgumentException("HTML contains potentially dangerous content");
            }
        }
    }
}