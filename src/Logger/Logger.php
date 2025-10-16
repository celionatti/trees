<?php

declare(strict_types=1);

namespace Trees\Logger;

use Trees\Exception\TreesException;

class Logger
{
    /**
     * @var string Log file path
     */
    private static string $logFile = 'storage/logs/logger.log';

    /**
     * @var int Log level (0 = none, 1 = errors, 2 = warnings, 3 = info)
     */
    private static int $logLevel = 3;

    /**
     * @var int Maximum number of log files to keep
     */
    private static int $maxLogFiles = 3;

    /**
     * @var int Maximum log file size in bytes (2MB default)
     */
    private static int $maxFileSize = 2 * 1024 * 1024;

    /**
     * Set the log file path
     *
     * @param string $path The log file path
     * @return void
     */
    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
    }

    /**
     * Set the log level
     *
     * @param int $level The log level
     * @return void
     */
    public static function setLogLevel(int $level): void
    {
        self::$logLevel = $level;
    }

    /**
     * Set the maximum number of log files to keep
     *
     * @param int $maxFiles Maximum number of log files
     * @return void
     */
    public static function setMaxLogFiles(int $maxFiles): void
    {
        self::$maxLogFiles = max(1, $maxFiles); // Ensure at least 1 file
    }

    /**
     * Set the maximum log file size in bytes
     *
     * @param int $size Maximum file size in bytes
     * @return void
     */
    public static function setMaxFileSize(int $size): void
    {
        self::$maxFileSize = max(1024, $size); // Minimum 1KB
    }

    /**
     * Log an error message
     *
     * @param string $message The error message
     * @param array $context Additional context data
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        if (self::$logLevel >= 1) {
            self::log('ERROR', $message, $context);
        }
    }

    /**
     * Log a warning message
     *
     * @param string $message The warning message
     * @param array $context Additional context data
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        if (self::$logLevel >= 2) {
            self::log('WARNING', $message, $context);
        }
    }

    /**
     * Log an info message
     *
     * @param string $message The info message
     * @param array $context Additional context data
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        if (self::$logLevel >= 3) {
            self::log('INFO', $message, $context);
        }
    }

    /**
     * Log a debug message
     *
     * @param string $message The debug message
     * @param array $context Additional context data
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        if (self::$logLevel >= 4) {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Log a message with rotation handling
     *
     * @param string $level The log level
     * @param string $message The log message
     * @param array $context Additional context data
     * @return void
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $logFilePath = ROOT_PATH . DIRECTORY_SEPARATOR . self::$logFile;
        $logDir = dirname($logFilePath);

        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                return;
            }
        }

        // Ensure the directory is writable
        if (!is_writable($logDir)) {
            return;
        }

        // Check if log rotation is needed
        if (file_exists($logFilePath) && filesize($logFilePath) > self::$maxFileSize) {
            self::rotateLogFiles($logFilePath);
        }

        // Format the log message
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        $logMessage = sprintf("[%s] [%s] %s%s%s", $timestamp, $level, $message, $contextStr, PHP_EOL);

        // Write to log file
        file_put_contents($logFilePath, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotate log files when they exceed the maximum size
     *
     * @param string $logFilePath The current log file path
     * @return void
     */
    private static function rotateLogFiles(string $logFilePath): void
    {
        $logDir = dirname($logFilePath);
        $logFileName = basename($logFilePath, '.log');
        $logFileExtension = pathinfo($logFilePath, PATHINFO_EXTENSION);

        // Shift existing rotated files
        for ($i = self::$maxLogFiles - 1; $i > 0; $i--) {
            $oldFile = $logDir . DIRECTORY_SEPARATOR . $logFileName . '.' . $i . '.' . $logFileExtension;
            $newFile = $logDir . DIRECTORY_SEPARATOR . $logFileName . '.' . ($i + 1) . '.' . $logFileExtension;

            if (file_exists($oldFile)) {
                if ($i + 1 > self::$maxLogFiles) {
                    // Delete the oldest file if it exceeds the limit
                    unlink($oldFile);
                } else {
                    // Move to the next rotation number
                    rename($oldFile, $newFile);
                }
            }
        }

        // Move current log file to .1
        $rotatedFile = $logDir . DIRECTORY_SEPARATOR . $logFileName . '.1.' . $logFileExtension;
        if (file_exists($logFilePath)) {
            rename($logFilePath, $rotatedFile);
        }

        // Clean up any excess files (safety check)
        self::cleanupOldLogFiles($logDir, $logFileName, $logFileExtension);
    }

    /**
     * Clean up old log files that exceed the maximum count
     *
     * @param string $logDir Log directory path
     * @param string $logFileName Base log file name
     * @param string $logFileExtension Log file extension
     * @return void
     */
    private static function cleanupOldLogFiles(string $logDir, string $logFileName, string $logFileExtension): void
    {
        $pattern = $logDir . DIRECTORY_SEPARATOR . $logFileName . '.*.'. $logFileExtension;
        $logFiles = glob($pattern);

        if ($logFiles === false) {
            return;
        }

        // Sort files by modification time (newest first)
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Remove files beyond the maximum count
        $filesToDelete = array_slice($logFiles, self::$maxLogFiles);
        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Log an exception with detailed context
     *
     * @param \Throwable $e The exception to log
     * @return void
     */
    public static function exception(\Throwable $e): void
    {
        $context = [
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];

        if ($e instanceof TreesException) {
            $context['query'] = $e->getMessage();
            $context['params'] = $e->getErrors();
            $context['error_code'] = $e->getCode();
        }

        self::error($e->getMessage(), $context);
    }

    /**
     * Get current log file size in bytes
     *
     * @return int File size in bytes, 0 if file doesn't exist
     */
    public static function getLogFileSize(): int
    {
        $logFilePath = ROOT_PATH . DIRECTORY_SEPARATOR . self::$logFile;
        return file_exists($logFilePath) ? filesize($logFilePath) : 0;
    }

    /**
     * Get list of all log files (current and rotated)
     *
     * @return array Array of log file paths
     */
    public static function getLogFiles(): array
    {
        $logFilePath = ROOT_PATH . DIRECTORY_SEPARATOR . self::$logFile;
        $logDir = dirname($logFilePath);
        $logFileName = basename($logFilePath, '.log');
        $logFileExtension = pathinfo($logFilePath, PATHINFO_EXTENSION);

        $files = [];
        
        // Add current log file if it exists
        if (file_exists($logFilePath)) {
            $files[] = $logFilePath;
        }

        // Add rotated files
        $pattern = $logDir . DIRECTORY_SEPARATOR . $logFileName . '.*.'. $logFileExtension;
        $rotatedFiles = glob($pattern);
        
        if ($rotatedFiles !== false) {
            $files = array_merge($files, $rotatedFiles);
        }

        return $files;
    }

    /**
     * Clear all log files
     *
     * @return bool True if successful, false otherwise
     */
    public static function clearLogs(): bool
    {
        $logFiles = self::getLogFiles();
        $success = true;

        foreach ($logFiles as $file) {
            if (file_exists($file) && !unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }
}