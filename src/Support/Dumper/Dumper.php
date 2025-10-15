<?php

declare(strict_types=1);

namespace Trees\Support\Dumper;

use ReflectionObject;
use ReflectionProperty;
use Error;
use Exception;
use Throwable;

class Dumper
{
    // Configuration defaults
    private static int $maxDepth = 5;
    private static int $maxArrayItems = 20;
    private static int $maxObjectProps = 20;
    private static int $maxStringLength = 500;
    private static bool $showCallerContext = true;
    private static bool $showMemoryUsage = true;
    private static bool $showStackTrace = true;
    private static bool $showFrameworkInfo = true;
    private static bool $showErrorSuggestions = true;
    private static bool $darkMode = false;
    private static int $stackTraceLimit = 10;

    // Internal state
    private static bool $cssLoaded = false;
    private static bool $jsLoaded = false;
    private static array $processedObjects = [];
    private static array $allowedIPs = ['127.0.0.1', '::1'];
    private static bool $initialized = false;

    // Trees Framework Colors
    private const PRIMARY_COLOR = '#10b981';      // Emerald
    private const SECONDARY_COLOR = '#059669';    // Darker Emerald
    private const ACCENT_COLOR = '#d97706';       // Amber
    private const DANGER_COLOR = '#dc2626';       // Red
    private const WARNING_COLOR = '#f59e0b';      // Yellow
    private const INFO_COLOR = '#3b82f6';         // Blue
    private const DARK_BG = '#1f2937';            // Dark gray
    private const LIGHT_BG = '#f9fafb';           // Light gray

    // Constants
    private const MAX_DEPTH_LIMIT = 10;
    private const MAX_ITEMS_LIMIT = 100;
    private const MAX_STRING_LIMIT = 2000;
    private const VERSION = '2.0.0';

    /**
     * Initialize the dumper with custom settings
     */
    public static function init(array $config = []): void
    {
        if (self::$initialized) {
            return;
        }

        self::$maxDepth = min($config['maxDepth'] ?? self::$maxDepth, self::MAX_DEPTH_LIMIT);
        self::$maxArrayItems = min($config['maxArrayItems'] ?? self::$maxArrayItems, self::MAX_ITEMS_LIMIT);
        self::$maxObjectProps = min($config['maxObjectProps'] ?? self::$maxObjectProps, self::MAX_ITEMS_LIMIT);
        self::$maxStringLength = min($config['maxStringLength'] ?? self::$maxStringLength, self::MAX_STRING_LIMIT);
        self::$showCallerContext = $config['showCallerContext'] ?? self::$showCallerContext;
        self::$showMemoryUsage = $config['showMemoryUsage'] ?? self::$showMemoryUsage;
        self::$showStackTrace = $config['showStackTrace'] ?? self::$showStackTrace;
        self::$showFrameworkInfo = $config['showFrameworkInfo'] ?? self::$showFrameworkInfo;
        self::$showErrorSuggestions = $config['showErrorSuggestions'] ?? self::$showErrorSuggestions;
        self::$darkMode = $config['darkMode'] ?? self::$darkMode;
        self::$stackTraceLimit = $config['stackTraceLimit'] ?? self::$stackTraceLimit;
        self::$allowedIPs = array_merge(self::$allowedIPs, $config['allowedIPs'] ?? []);

        self::$initialized = true;
    }

    /**
     * Dump variables with full context
     */
    public static function dump(...$vars): void
    {
        if (!self::isDebuggingAllowed()) {
            return;
        }

        self::$processedObjects = [];
        ob_start();

        $groupId = uniqid('dump-');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::$stackTraceLimit + 5);
        $relevantTrace = self::getRelevantBacktrace();

        self::loadAssets($groupId);

        echo "<div class='trees-dump-wrapper' id='{$groupId}'>";
        echo self::getFrameworkHeader();
        echo self::getLocationBar($relevantTrace);
        
        echo "<div class='dump-grid-container'>";
        
        // Left sidebar with context and info
        echo "<div class='dump-sidebar'>";
        if (self::$showFrameworkInfo) {
            echo self::getFrameworkInfoPanel();
        }
        if (self::$showCallerContext) {
            echo self::getCallerContextPanel($relevantTrace);
        }
        if (self::$showErrorSuggestions) {
            echo self::getErrorSuggestionsPanel($vars);
        }
        if (self::$showStackTrace) {
            echo self::getStackTracePanel($backtrace);
        }
        echo "</div>";

        // Main content area with variables
        echo "<div class='dump-main-content'>";
        echo "<div class='dump-variables-header'>";
        echo "<h3>Dumped Variables (" . count($vars) . ")</h3>";
        echo "<div class='dump-controls'>";
        echo "<button class='dump-btn dump-expand-all' data-group='{$groupId}'>🌳 Expand All</button>";
        echo "<button class='dump-btn dump-collapse-all' data-group='{$groupId}'>🍃 Collapse All</button>";
        echo "<button class='dump-btn dump-export-json' data-group='{$groupId}'>📥 Export JSON</button>";
        echo "</div>";
        echo "</div>";

        echo "<div class='dump-variables-grid'>";
        foreach ($vars as $index => $var) {
            self::renderVariable($var, $groupId, $index + 1);
        }
        echo "</div>";
        echo "</div>";
        
        echo "</div>"; // .dump-grid-container
        
        echo "</div>"; // .trees-dump-wrapper

        ob_end_flush();
    }

    /**
     * Dump and die
     */
    public static function dd(...$vars): void
    {
        self::dump(...$vars);
        die(1);
    }

    /**
     * Quick dump for large data structures
     */
    public static function quickDump(...$vars): void
    {
        if (!self::isDebuggingAllowed()) {
            return;
        }

        ob_start();

        $backtrace = self::getRelevantBacktrace();
        $location = basename($backtrace['file'] ?? 'unknown') . ':' . ($backtrace['line'] ?? 'unknown');

        echo "<div class='trees-quick-dump'>";
        echo "<div class='quick-dump-header'>";
        echo "<span class='quick-dump-icon'>🌲</span>";
        echo "<span>Quick Dump @ {$location}</span>";
        echo "</div>";

        echo "<pre class='quick-dump-content'>";
        foreach ($vars as $var) {
            echo htmlspecialchars(print_r($var, true)), "\n---\n";
        }
        echo "</pre></div>";

        ob_end_flush();
    }

    /**
     * Log dump to file
     */
    public static function log(...$vars): void
    {
        $backtrace = self::getRelevantBacktrace();
        $location = basename($backtrace['file'] ?? 'unknown') . ':' . ($backtrace['line'] ?? 'unknown');
        $timestamp = date('Y-m-d H:i:s');
        
        $logContent = "[$timestamp] $location\n";
        foreach ($vars as $index => $var) {
            $logContent .= "Variable #" . ($index + 1) . ":\n";
            $logContent .= print_r($var, true) . "\n\n";
        }
        
        $logFile = sys_get_temp_dir() . '/trees-dump.log';
        error_log($logContent, 3, $logFile);
    }

    /**
     * Check if debugging is allowed
     */
    private static function isDebuggingAllowed(): bool
    {
        $env = strtolower($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production');
        if (!in_array($env, ['local', 'development', 'dev', 'testing', 'staging'])) {
            return false;
        }

        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!in_array($clientIP, self::$allowedIPs) && !in_array('*', self::$allowedIPs)) {
            return false;
        }

        if (headers_sent()) {
            return false;
        }

        return true;
    }

    /**
     * Get the most relevant backtrace entry
     */
    private static function getRelevantBacktrace(): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($backtrace as $entry) {
            if (!isset($entry['class']) || strpos($entry['class'], 'Trees\\Support\\Dumper\\') !== 0) {
                return $entry;
            }
        }

        return $backtrace[0] ?? [];
    }

    /**
     * Get framework info panel
     */
    private static function getFrameworkInfoPanel(): string
    {
        $phpVersion = PHP_VERSION;
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = self::formatMemory(memory_get_usage(true));
        $peakMemory = self::formatMemory(memory_get_peak_usage(true));
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'unknown';

        return <<<HTML
        <div class='info-panel'>
            <div class='panel-header'>
                <span class='panel-icon'>🌲</span>
                <h3>Trees Framework</h3>
            </div>
            <div class='panel-content'>
                <div class='info-row'>
                    <span class='info-label'>PHP Version</span>
                    <span class='info-value'>{$phpVersion}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Environment</span>
                    <span class='info-value env-badge'>{$env}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Memory Usage</span>
                    <span class='info-value'>{$memoryUsage}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Peak Memory</span>
                    <span class='info-value'>{$peakMemory}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Memory Limit</span>
                    <span class='info-value'>{$memoryLimit}</span>
                </div>
                <div class='info-row'>
                    <span class='info-label'>Dumper Version</span>
                    <span class='info-value'>v{self::VERSION}</span>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Get caller context panel
     */
    private static function getCallerContextPanel(array $backtrace): string
    {
        if (!isset($backtrace['file']) || !is_readable($backtrace['file'])) {
            return '';
        }

        $line = $backtrace['line'] ?? 0;
        $file = $backtrace['file'];
        $fileContent = file($file);
        $start = max(0, $line - 5);
        $end = min(count($fileContent), $line + 5);
        $context = [];

        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $content = htmlspecialchars($fileContent[$i] ?? '', ENT_QUOTES, 'UTF-8');
            $highlight = ($currentLine === $line) ? 'highlight' : '';
            $context[] = sprintf(
                '<div class="code-line %s"><span class="line-number">%d</span><span class="line-content">%s</span></div>',
                $highlight,
                $currentLine,
                $content
            );
        }

        $fileName = basename($file);
        $contextHtml = implode("\n", $context);

        return <<<HTML
        <div class='info-panel'>
            <div class='panel-header'>
                <span class='panel-icon'>📄</span>
                <h3>Code Context</h3>
            </div>
            <div class='panel-content no-padding'>
                <div class='context-file-name'>{$fileName}</div>
                <div class='code-lines'>
                    {$contextHtml}
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Get error suggestions panel
     */
    private static function getErrorSuggestionsPanel(array $vars): string
    {
        $suggestions = [];
        
        foreach ($vars as $var) {
            if ($var instanceof Throwable) {
                $suggestions[] = self::analyzeException($var);
            } elseif (is_null($var)) {
                $suggestions[] = "• Variable is null - check if it's properly initialized";
            } elseif (is_array($var) && empty($var)) {
                $suggestions[] = "• Array is empty - verify data source or query";
            } elseif (is_string($var) && trim($var) === '') {
                $suggestions[] = "• String is empty - check input validation";
            }
        }

        if (empty($suggestions)) {
            $suggestions[] = "• All variables appear normal";
            $suggestions[] = "• Check data types match expectations";
            $suggestions[] = "• Verify business logic flow";
        }

        $suggestionsHtml = implode('<br>', array_slice($suggestions, 0, 5));

        return <<<HTML
        <div class='info-panel'>
            <div class='panel-header'>
                <span class='panel-icon'>💡</span>
                <h3>Suggestions</h3>
            </div>
            <div class='panel-content'>
                <div class='suggestions-list'>
                    {$suggestionsHtml}
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Analyze exception for suggestions
     */
    private static function analyzeException(Throwable $e): string
    {
        $message = $e->getMessage();
        $suggestions = [];

        if (strpos($message, 'undefined variable') !== false) {
            $suggestions[] = "• Check variable spelling and scope";
            $suggestions[] = "• Ensure variable is defined before use";
        } elseif (strpos($message, 'call to undefined function') !== false) {
            $suggestions[] = "• Verify function name spelling";
            $suggestions[] = "• Check if extension is installed";
        } elseif (strpos($message, 'class not found') !== false) {
            $suggestions[] = "• Check class namespace and autoloading";
            $suggestions[] = "• Verify composer autoloader";
        } elseif (strpos($message, 'syntax error') !== false) {
            $suggestions[] = "• Check for missing semicolons or brackets";
            $suggestions[] = "• Validate PHP syntax in the file";
        }

        return implode('<br>', array_slice($suggestions, 0, 2));
    }

    /**
     * Get stack trace panel
     */
    private static function getStackTracePanel(array $backtrace): string
    {
        $traces = [];
        $count = 0;

        foreach ($backtrace as $index => $trace) {
            if ($count >= self::$stackTraceLimit) {
                break;
            }

            // Skip dumper internal calls
            if (isset($trace['class']) && strpos($trace['class'], 'Trees\\Support\\Dumper\\') === 0) {
                continue;
            }

            $file = isset($trace['file']) ? basename($trace['file']) : 'unknown';
            $line = $trace['line'] ?? '?';
            $function = $trace['function'] ?? 'unknown';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';

            $call = $class ? "{$class}{$type}{$function}()" : "{$function}()";

            $traces[] = <<<HTML
            <div class='trace-item'>
                <div class='trace-number'>{$count}</div>
                <div class='trace-details'>
                    <div class='trace-call'>{$call}</div>
                    <div class='trace-location'>{$file}:{$line}</div>
                </div>
            </div>
            HTML;

            $count++;
        }

        if (empty($traces)) {
            return '';
        }

        $tracesHtml = implode("\n", $traces);
        
        return <<<HTML
        <div class='info-panel'>
            <div class='panel-header'>
                <span class='panel-icon'>📚</span>
                <h3>Stack Trace</h3>
            </div>
            <div class='panel-content no-padding'>
                <div class='stack-trace'>
                    {$tracesHtml}
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Load required CSS and JavaScript
     */
    private static function loadAssets(string $groupId): void
    {
        if (!self::$cssLoaded) {
            echo self::getCss();
            self::$cssLoaded = true;
        }

        if (!self::$jsLoaded) {
            echo self::getJavaScript($groupId);
            self::$jsLoaded = true;
        }
    }

    private static function getCss(): string
    {
        $primary = self::PRIMARY_COLOR;
        $secondary = self::SECONDARY_COLOR;
        $accent = self::ACCENT_COLOR;
        $danger = self::DANGER_COLOR;
        $warning = self::WARNING_COLOR;
        $info = self::INFO_COLOR;
        $darkBg = self::DARK_BG;
        $lightBg = self::LIGHT_BG;

        return <<<CSS
        <style>
        * { box-sizing: border-box; }
        
        .trees-dump-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 24px auto;
            max-width: 100%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.15);
            background: #ffffff;
            border: 2px solid {$primary}20;
        }

        .trees-framework-header {
            background: linear-gradient(135deg, {$primary} 0%, {$secondary} 100%);
            color: white;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid {$secondary};
            position: relative;
            overflow: hidden;
        }

        .trees-framework-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><path d="M30,20 L40,50 L60,50 L70,20 L50,5 Z" fill="white"/><path d="M35,50 L45,80 L55,80 L65,50 L50,35 Z" fill="white"/></svg>') repeat;
        }

        .trees-brand {
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            z-index: 2;
        }

        .trees-logo {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            color: white;
            letter-spacing: -1px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .trees-title {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .trees-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            margin: 4px 0 0 0;
            font-weight: 500;
        }

        .trees-info-badge {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            z-index: 2;
        }

        .dump-location-bar {
            background: {$lightBg};
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid {$primary}20;
            flex-wrap: wrap;
            gap: 16px;
        }

        .location-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
        }

        .location-info strong {
            color: {$secondary};
            font-weight: 700;
        }

        .dump-grid-container {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 0;
            background: {$lightBg};
            min-height: 600px;
        }

        .dump-sidebar {
            background: #ffffff;
            border-right: 2px solid {$primary}20;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-height: 900px;
            overflow-y: auto;
        }

        .dump-main-content {
            padding: 24px;
            min-height: 400px;
            background: #ffffff;
        }

        .dump-variables-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid {$primary}20;
        }

        .dump-variables-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: {$secondary};
        }

        .info-panel {
            background: #ffffff;
            border: 2px solid {$primary}20;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .info-panel:hover {
            border-color: {$primary}40;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.1);
        }

        .panel-header {
            background: linear-gradient(135deg, {$primary}15, {$primary}05);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid {$primary}20;
        }

        .panel-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: {$secondary};
        }

        .panel-icon {
            font-size: 18px;
        }

        .panel-content {
            padding: 20px;
        }

        .panel-content.no-padding {
            padding: 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid {$primary}10;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
        }

        .info-value {
            font-size: 13px;
            color: #111827;
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .env-badge {
            background: {$primary};
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .context-file-name {
            padding: 12px 20px;
            background: {$primary}08;
            font-size: 12px;
            font-weight: 700;
            color: {$secondary};
            font-family: 'Monaco', 'Menlo', monospace;
            border-bottom: 1px solid {$primary}20;
        }

        .code-lines {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
            font-size: 13px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }

        .code-line {
            display: flex;
            padding: 4px 0;
            transition: background 0.2s;
        }

        .code-line:hover {
            background: {$primary}05;
        }

        .code-line.highlight {
            background: {$warning}20;
            border-left: 3px solid {$warning};
        }

        .line-number {
            color: #9ca3af;
            padding: 0 16px 0 20px;
            text-align: right;
            user-select: none;
            min-width: 60px;
            font-weight: 500;
        }

        .line-content {
            flex: 1;
            padding-right: 20px;
            color: #374151;
            font-weight: 500;
        }

        .stack-trace {
            max-height: 400px;
            overflow-y: auto;
        }

        .trace-item {
            display: flex;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid {$primary}10;
            transition: all 0.2s;
        }

        .trace-item:hover {
            background: {$primary}05;
            transform: translateX(2px);
        }

        .trace-item:last-child {
            border-bottom: none;
        }

        .trace-number {
            background: {$primary};
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .trace-details {
            flex: 1;
            min-width: 0;
        }

        .trace-call {
            font-size: 14px;
            font-weight: 700;
            color: {$secondary};
            font-family: 'Monaco', 'Menlo', monospace;
            margin-bottom: 4px;
            word-break: break-all;
        }

        .trace-location {
            font-size: 12px;
            color: #6b7280;
            font-family: 'Monaco', 'Menlo', monospace;
            font-weight: 500;
        }

        .suggestions-list {
            font-size: 13px;
            line-height: 1.6;
            color: #6b7280;
        }

        .dump-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .dump-btn {
            background: #ffffff;
            border: 2px solid {$primary}30;
            color: {$secondary};
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dump-btn:hover {
            background: {$primary}10;
            border-color: {$primary};
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .dump-variables-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        }

        .dump-variable-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid {$primary}20;
            transition: all 0.3s ease;
        }

        .dump-variable-card:hover {
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
            transform: translateY(-4px);
            border-color: {$primary}40;
        }

        .dump-variable-header {
            background: linear-gradient(135deg, {$secondary}, {$primary});
            color: white;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dump-variable-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .variable-number {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
            min-width: 32px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .dump-type-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        .dump-class-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: white;
            backdrop-filter: blur(10px);
        }

        .dump-variable-actions {
            display: flex;
            gap: 8px;
        }

        .dump-toggle, .dump-copy {
            background: rgba(255, 255, 255, 0.25);
            border: none;
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .dump-toggle:hover, .dump-copy:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.05);
        }

        .dump-content {
            background: {$darkBg};
            color: #e5e7eb;
            padding: 24px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.7;
            overflow-x: auto;
            white-space: pre;
            max-height: 600px;
            overflow-y: auto;
        }

        .dump-content.collapsed {
            display: none;
        }

        .dump-content::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .dump-content::-webkit-scrollbar-track {
            background: #111827;
        }

        .dump-content::-webkit-scrollbar-thumb {
            background: {$primary};
            border-radius: 6px;
        }

        .dump-content::-webkit-scrollbar-thumb:hover {
            background: {$secondary};
        }

        .dump-sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .dump-sidebar::-webkit-scrollbar-track {
            background: #f9fafb;
        }

        .dump-sidebar::-webkit-scrollbar-thumb {
            background: {$primary};
            border-radius: 4px;
        }

        /* Syntax highlighting */
        .dump-string { color: #34d399; font-weight: 600; }
        .dump-number { color: #a78bfa; font-weight: 600; }
        .dump-boolean { color: #f87171; font-weight: 600; }
        .dump-null { color: #9ca3af; font-style: italic; font-weight: 600; }
        .dump-array-key { color: #fbbf24; font-weight: 600; }
        .dump-object-property { color: #60a5fa; font-weight: 600; }
        .dump-visibility { color: #a78bfa; font-style: italic; font-weight: 600; }
        .dump-resource { color: #fb923c; font-weight: 600; }
        .dump-class-name { color: #34d399; font-weight: 800; }
        .dump-truncated { color: #9ca3af; font-style: italic; }
        .dump-circular { color: #f87171; font-style: italic; font-weight: 600; }

        /* Quick dump styles */
        .trees-quick-dump {
            margin: 20px auto;
            max-width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
            border: 2px solid {$primary}30;
        }

        .quick-dump-header {
            background: linear-gradient(135deg, {$primary}, {$secondary});
            color: white;
            padding: 16px 20px;
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-dump-icon {
            font-size: 18px;
        }

        .quick-dump-content {
            background: {$darkBg};
            color: #e5e7eb;
            padding: 24px;
            margin: 0;
            font-size: 14px;
            max-height: 400px;
            overflow: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .dump-grid-container {
                grid-template-columns: 350px 1fr;
            }
        }

        @media (max-width: 968px) {
            .dump-grid-container {
                grid-template-columns: 1fr;
            }

            .dump-sidebar {
                border-right: none;
                border-bottom: 2px solid {$primary}20;
                max-height: none;
            }

            .trees-dump-wrapper {
                margin: 16px;
            }
        }

        @media (max-width: 768px) {
            .trees-framework-header {
                padding: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .dump-variables-grid {
                grid-template-columns: 1fr;
            }

            .dump-location-bar {
                flex-direction: column;
                align-items: flex-start;
                padding: 16px 20px;
            }

            .dump-main-content {
                padding: 20px;
            }

            .dump-sidebar {
                padding: 20px;
            }

            .dump-controls {
                width: 100%;
            }

            .dump-btn {
                flex: 1;
                justify-content: center;
            }

            .trees-logo {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .trees-title {
                font-size: 20px;
            }

            .dump-variables-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            .dump-content {
                font-size: 12px;
                padding: 20px;
            }

            .code-lines {
                font-size: 12px;
            }

            .line-number {
                padding: 0 12px 0 16px;
                min-width: 50px;
            }

            .line-content {
                padding-right: 16px;
            }

            .dump-variables-grid {
                grid-template-columns: 1fr;
            }
            
            .trees-dump-wrapper {
                margin: 12px;
                border-radius: 12px;
            }
        }
        </style>
        CSS;
    }

    private static function getJavaScript(string $groupId): string
    {
        return <<<'JS'
        <script>
        (function() {
            'use strict';

            document.addEventListener('DOMContentLoaded', function() {
                // Toggle individual variables
                document.querySelectorAll('.dump-toggle').forEach(button => {
                    button.addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');
                        const target = document.getElementById(targetId);
                        const isCollapsed = target.classList.contains('collapsed');

                        target.classList.toggle('collapsed');
                        this.textContent = isCollapsed ? '▼' : '▶';
                    });
                });

                // Copy functionality
                document.querySelectorAll('.dump-copy').forEach(button => {
                    button.addEventListener('click', async function() {
                        const card = this.closest('.dump-variable-card');
                        const content = card.querySelector('.dump-content').textContent;
                        const originalText = this.textContent;

                        try {
                            if (navigator.clipboard) {
                                await navigator.clipboard.writeText(content);
                            } else {
                                const textArea = document.createElement('textarea');
                                textArea.value = content;
                                textArea.style.position = 'fixed';
                                textArea.style.left = '-9999px';
                                document.body.appendChild(textArea);
                                textArea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textArea);
                            }

                            this.textContent = '✓ Copied';
                            setTimeout(() => this.textContent = originalText, 2000);
                        } catch (err) {
                            this.textContent = '✗ Failed';
                            setTimeout(() => this.textContent = originalText, 2000);
                        }
                    });
                });

                // Expand/Collapse all
                document.querySelectorAll('.dump-expand-all').forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-group');
                        document.querySelectorAll(`#${groupId} .dump-toggle`).forEach(toggle => {
                            const targetId = toggle.getAttribute('data-target');
                            const target = document.getElementById(targetId);
                            target.classList.remove('collapsed');
                            toggle.textContent = '▼';
                        });
                    });
                });

                document.querySelectorAll('.dump-collapse-all').forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-group');
                        document.querySelectorAll(`#${groupId} .dump-toggle`).forEach(toggle => {
                            const targetId = toggle.getAttribute('data-target');
                            const target = document.getElementById(targetId);
                            target.classList.add('collapsed');
                            toggle.textContent = '▶';
                        });
                    });
                });

                // Export to JSON
                document.querySelectorAll('.dump-export-json').forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-group');
                        const cards = document.querySelectorAll(`#${groupId} .dump-variable-card`);
                        const data = [];

                        cards.forEach(card => {
                            const content = card.querySelector('.dump-content').textContent;
                            data.push(content);
                        });

                        const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `trees-dump-${Date.now()}.json`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    });
                });
            });
        })();
        </script>
        JS;
    }

    private static function getFrameworkHeader(): string
    {
        $timestamp = date('Y-m-d H:i:s');

        return <<<HTML
        <div class='trees-framework-header'>
            <div class='trees-brand'>
                <div class='trees-logo'>🌲</div>
                <div>
                    <h2 class='trees-title'>Trees Framework</h2>
                    <p class='trees-subtitle'>Debug Data Dumper</p>
                </div>
            </div>
            <div class='trees-info-badge'>
                {$timestamp}
            </div>
        </div>
        HTML;
    }

    private static function getLocationBar(array $backtrace): string
    {
        if (!is_array($backtrace)) {
            $backtrace = [];
        }

        $file = isset($backtrace['file']) && is_string($backtrace['file']) 
            ? htmlspecialchars($backtrace['file'], ENT_QUOTES, 'UTF-8') 
            : 'unknown';
        $line = htmlspecialchars((string)($backtrace['line'] ?? 'unknown'), ENT_QUOTES, 'UTF-8');
        $fileName = basename($file);

        return <<<HTML
        <div class='dump-location-bar'>
            <div class='location-info'>
                <span class='location-icon'>📍</span>
                <strong>{$fileName}</strong>
                <span>•</span>
                <span>Line {$line}</span>
            </div>
        </div>
        HTML;
    }

    private static function renderVariable($var, string $groupId, int $varNumber): void
    {
        $varId = uniqid('var-');
        $varType = gettype($var);
        $classInfo = is_object($var) ? get_class($var) : '';
        $shortClass = $classInfo ? basename(str_replace('\\', '/', $classInfo)) : '';

        echo "<div class='dump-variable-card'>";
        echo "<div class='dump-variable-header'>";
        echo "<div class='dump-variable-info'>";
        echo "<span class='variable-number'>{$varNumber}</span>";
        echo "<span class='dump-type-badge'>{$varType}</span>";

        if ($shortClass) {
            echo "<span class='dump-class-badge'>{$shortClass}</span>";
        }

        echo "</div>";
        echo "<div class='dump-variable-actions'>";
        echo "<button class='dump-toggle' data-target='{$varId}'>▼</button>";
        echo "<button class='dump-copy'>📋 Copy</button>";
        echo "</div>";
        echo "</div>";

        echo "<div id='{$varId}' class='dump-content'>";
        echo self::formatVariable($var);
        echo "</div>";
        echo "</div>";
    }

    private static function formatVariable($var, int $depth = 0, string $path = ''): string
    {
        if ($depth > self::$maxDepth) {
            return '<span class="dump-truncated">[Max depth reached]</span>';
        }

        $spaces = str_repeat('  ', $depth);
        $type = gettype($var);

        switch ($type) {
            case 'NULL':
                return '<span class="dump-null">null</span>';

            case 'boolean':
                return '<span class="dump-boolean">' . ($var ? 'true' : 'false') . '</span>';

            case 'integer':
            case 'double':
                return '<span class="dump-number">' . $var . '</span>';

            case 'string':
                return self::formatString($var);

            case 'array':
                return self::formatArray($var, $depth, $spaces, $path);

            case 'object':
                return self::formatObject($var, $depth, $spaces, $path);

            case 'resource':
            case 'resource (closed)':
                return '<span class="dump-resource">' . $type . '(' . get_resource_type($var) . ')</span>';

            default:
                return '<span class="dump-null">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    private static function formatString(string $var): string
    {
        $length = strlen($var);
        $isBinary = !mb_check_encoding($var, 'UTF-8');

        if ($isBinary) {
            return '<span class="dump-string">[binary data]</span> <span class="dump-truncated">(' . $length . ' bytes)</span>';
        }

        if ($length > self::$maxStringLength) {
            $preview = substr($var, 0, self::$maxStringLength);
            return '<span class="dump-string">"' . htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') .
                '..."</span> <span class="dump-truncated">(' . $length . ' chars)</span>';
        }

        return '<span class="dump-string">"' . htmlspecialchars($var, ENT_QUOTES, 'UTF-8') . '"</span>';
    }

    private static function formatArray(array $var, int $depth, string $spaces, string $path): string
    {
        $count = count($var);

        if ($count === 0) {
            return '<span class="dump-array-key">array(0)</span> []';
        }

        $output = "<span class=\"dump-array-key\">array({$count})</span> [\n";
        $itemCount = 0;

        foreach ($var as $key => $value) {
            if ($itemCount >= self::$maxArrayItems) {
                $remaining = $count - self::$maxArrayItems;
                $output .= "{$spaces}  <span class=\"dump-truncated\">... {$remaining} more items</span>\n";
                break;
            }

            $formattedKey = is_int($key)
                ? "<span class=\"dump-number\">{$key}</span>"
                : "<span class=\"dump-string\">\"" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "\"</span>";

            $newPath = $path ? "{$path}[{$key}]" : "[{$key}]";

            $output .= sprintf(
                "%s  %s => %s,\n",
                $spaces,
                $formattedKey,
                self::formatVariable($value, $depth + 1, $newPath)
            );

            $itemCount++;
        }

        return $output . $spaces . ']';
    }

    private static function formatObject($var, int $depth, string $spaces, string $path): string
    {
        $className = get_class($var);
        $objectId = spl_object_id($var);
        $shortClassName = basename(str_replace('\\', '/', $className));

        if (isset(self::$processedObjects[$objectId])) {
            return '<span class="dump-circular">*CIRCULAR REFERENCE* ' . $shortClassName . '</span>';
        }

        self::$processedObjects[$objectId] = true;

        try {
            $reflection = new ReflectionObject($var);
            $properties = $reflection->getProperties();

            $output = "<span class=\"dump-class-name\">{$shortClassName}</span> {\n";
            $propCount = 0;
            $totalProps = count($properties);
            $maxProps = min(self::$maxObjectProps, $totalProps);

            foreach ($properties as $property) {
                if ($propCount >= $maxProps) {
                    $remaining = $totalProps - $maxProps;
                    $output .= "{$spaces}  <span class=\"dump-truncated\">... {$remaining} more properties</span>\n";
                    break;
                }

                $property->setAccessible(true);
                $modifiers = self::getPropertyModifiers($property);
                $propertyName = $property->getName();
                $newPath = $path ? "{$path}->{$propertyName}" : "->{$propertyName}";

                try {
                    $value = $property->getValue($var);
                    $formattedValue = self::formatVariable($value, $depth + 1, $newPath);
                } catch (Throwable $e) {
                    $formattedValue = '<span class="dump-null">[uninitialized]</span>';
                }

                $output .= sprintf(
                    "%s  [<span class=\"dump-visibility\">%s</span>] <span class=\"dump-object-property\">%s</span> => %s,\n",
                    $spaces,
                    $modifiers,
                    $propertyName,
                    $formattedValue
                );

                $propCount++;
            }

            $result = $output . $spaces . '}';
        } catch (Throwable $e) {
            $result = '<span class="dump-class-name">' . $shortClassName . '</span> <span class="dump-null">[Error: ' .
                htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ']</span>';
        } finally {
            unset(self::$processedObjects[$objectId]);
        }

        return $result;
    }

    private static function getPropertyModifiers(ReflectionProperty $property): string
    {
        $modifiers = [];

        if ($property->isPublic()) {
            $modifiers[] = 'public';
        } elseif ($property->isProtected()) {
            $modifiers[] = 'protected';
        } elseif ($property->isPrivate()) {
            $modifiers[] = 'private';
        }

        if ($property->isStatic()) {
            $modifiers[] = 'static';
        }

        if (method_exists($property, 'isReadOnly') && $property->isReadOnly()) {
            $modifiers[] = 'readonly';
        }

        return implode(' ', $modifiers);
    }

    private static function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return sprintf('%.2f %s', $bytes, $units[$index]);
    }

    // Configuration setters
    public static function setMaxDepth(int $depth): void
    {
        self::$maxDepth = max(1, min(self::MAX_DEPTH_LIMIT, $depth));
    }

    public static function setMaxArrayItems(int $items): void
    {
        self::$maxArrayItems = max(1, min(self::MAX_ITEMS_LIMIT, $items));
    }

    public static function setMaxObjectProps(int $props): void
    {
        self::$maxObjectProps = max(1, min(self::MAX_ITEMS_LIMIT, $props));
    }

    public static function setMaxStringLength(int $length): void
    {
        self::$maxStringLength = max(10, min(self::MAX_STRING_LIMIT, $length));
    }

    public static function setDarkMode(bool $enabled): void
    {
        self::$darkMode = $enabled;
    }

    public static function setShowCallerContext(bool $enabled): void
    {
        self::$showCallerContext = $enabled;
    }

    public static function setShowMemoryUsage(bool $enabled): void
    {
        self::$showMemoryUsage = $enabled;
    }

    public static function setShowStackTrace(bool $enabled): void
    {
        self::$showStackTrace = $enabled;
    }

    public static function setShowFrameworkInfo(bool $enabled): void
    {
        self::$showFrameworkInfo = $enabled;
    }

    public static function setShowErrorSuggestions(bool $enabled): void
    {
        self::$showErrorSuggestions = $enabled;
    }

    public static function setStackTraceLimit(int $limit): void
    {
        self::$stackTraceLimit = max(1, min(50, $limit));
    }

    public static function setAllowedIPs(array $ips): void
    {
        self::$allowedIPs = $ips;
    }

    public static function addAllowedIP(string $ip): void
    {
        if (!in_array($ip, self::$allowedIPs)) {
            self::$allowedIPs[] = $ip;
        }
    }

    public static function removeAllowedIP(string $ip): void
    {
        self::$allowedIPs = array_values(array_diff(self::$allowedIPs, [$ip]));
    }

    public static function clearAllowedIPs(): void
    {
        self::$allowedIPs = ['127.0.0.1', '::1'];
    }

    public static function getConfig(): array
    {
        return [
            'maxDepth' => self::$maxDepth,
            'maxArrayItems' => self::$maxArrayItems,
            'maxObjectProps' => self::$maxObjectProps,
            'maxStringLength' => self::$maxStringLength,
            'showCallerContext' => self::$showCallerContext,
            'showMemoryUsage' => self::$showMemoryUsage,
            'showStackTrace' => self::$showStackTrace,
            'showFrameworkInfo' => self::$showFrameworkInfo,
            'showErrorSuggestions' => self::$showErrorSuggestions,
            'darkMode' => self::$darkMode,
            'stackTraceLimit' => self::$stackTraceLimit,
            'allowedIPs' => self::$allowedIPs,
        ];
    }

    public static function resetConfig(): void
    {
        self::$maxDepth = 5;
        self::$maxArrayItems = 20;
        self::$maxObjectProps = 20;
        self::$maxStringLength = 500;
        self::$showCallerContext = true;
        self::$showMemoryUsage = true;
        self::$showStackTrace = true;
        self::$showFrameworkInfo = true;
        self::$showErrorSuggestions = true;
        self::$darkMode = false;
        self::$stackTraceLimit = 10;
        self::$allowedIPs = ['127.0.0.1', '::1'];
        self::$initialized = false;
    }
}