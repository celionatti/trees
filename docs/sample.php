<?php

// In your controller or handler:

// Basic sanitization (built-in)
$safeContent = $request->getSafeHtml('content');

// Advanced sanitization with HTML Purifier
$purifiedContent = $request->getPurifiedHtml('content');

// Using the separate service
$sanitizer = new \Trees\Security\HtmlSanitizer();
$content = $request->getParsedBody()['content'] ?? '';
$safeContent = $sanitizer->sanitizeWithHtmlPurifier($content);