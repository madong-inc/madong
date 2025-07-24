<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: https://madong.tech
 */

namespace core\logger;

use core\logger\enum\LogLevel;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

final class LoggerService
{
    private Monolog $logger;

    public function __construct()
    {

        $this->initialize();
    }

    private function initialize(): void
    {
        if (!self::getConfig('enable')) {
            throw new \RuntimeException('Logger is disabled in config');
        }

        $this->logger = new Monolog(self::getConfig('base.channel'));

        if (self::getConfig('handlers.stream')) {
            $this->addStreamHandlers();
        }

        if (self::getConfig('handlers.syslog')) {
            $this->addSyslogHandler();
        }
    }

    public function handleLog(string $levelName, array $arguments): void
    {
        $level = LogLevel::fromName(strtoupper($levelName));
        if ($level === null) {
            return;
        }

        // 双重检查：全局启用 + 级别启用
        if (!self::getConfig('enable') || !$this->shouldRecord($level)) {
            return;
        }

        $this->logger->log(
            $level->value,
            $arguments[0] ?? '',
            $arguments[1] ?? []
        );
    }

    private function addStreamHandlers(): void
    {
        $formatter = new LineFormatter(
            self::getConfig('format.output'),
            self::getConfig('format.date'),
            true,
            true
        );

        // 为每个级别创建专属handler
        foreach (LogLevel::cases() as $level) {
            if ($this->shouldRecord($level)) {
                $handler = new StreamHandler(
                    $this->getLogFile($level),
                    $level->value, // 精确设置级别阈值
                    true,
                    null,
                    true
                );
                $handler->setFormatter($formatter);
                $handler->setLevel($level->value); // 关键修复：严格级别匹配
                $handler->setBubble(false); // 禁止日志传递
                $this->logger->pushHandler($handler);
            }
        }
    }

    private function shouldRecord(LogLevel $level): bool
    {
        // 1. 全局开关检查
        if (!self::getConfig('enable')) {
            return false;
        }

        // 2. Debug级别特殊控制
        if ($level === LogLevel::DEBUG &&
            false === self::getConfig('levels.debug', true)) {
            return false;
        }

        // 3. 最小级别控制
        $minLevel = LogLevel::fromName(
            strtoupper(self::getConfig('levels.min_level', 'debug'))
        );

        return $level->value >= $minLevel->value;
    }

    private function addSyslogHandler(): void
    {
        // syslog处理器实现
    }

    /**
     * @param string|null $key
     * @param             $default
     *
     * @return array|mixed|null
     */
    public static function getConfig(string $key = null, $default = null): mixed
    {
        $config = config('core.logger.app', []);
        return data_get($config, $key, $default);
    }

    /**
     * 获取按通道和月份分类的日志文件路径
     */
//    private function getLogFile(LogLevel $level): string
//    {
//        $basePath  = $this->getBaseLogPath();
//        $monthDir  = date('Y-m');
//        $dayPrefix = date('d'); // 获取当月日期数字
//
//        $fullPath = "{$basePath}/{$monthDir}";
//
//        // 自动创建月目录
//        if (!is_dir($fullPath) && !mkdir($fullPath, 0755, true)) {
//            throw new \RuntimeException("Cannot create month directory: {$fullPath}");
//        }
//
//        return sprintf('%s/%s-%s.log', $fullPath, $dayPrefix, $level->name);
//    }

    private function getLogFile(LogLevel $level): string
    {
        $basePath  = $this->getBaseLogPath();
        $monthDir  = date('Y-m');
        $dayPrefix = date('d');

        $fullPath = "{$basePath}/{$monthDir}";

        if (!is_dir($fullPath) && !mkdir($fullPath, 0755, true)) {
            throw new \RuntimeException("Cannot create directory: {$fullPath}");
        }

        // 保持原格式：DD-LEVEL.log
        return sprintf('%s/%s-%s.log', $fullPath, $dayPrefix, $level->name);
    }

    /**
     * 获取基础日志路径（包含通道目录）
     */
    private function getBaseLogPath(): string
    {
        $path    = rtrim(self::getConfig('base.path'), '/');
        $channel = self::getConfig('base.channel', 'app');

        $fullPath = "{$path}/{$channel}";

        // 通道目录不存在时自动创建
        if (!is_dir($fullPath) && !mkdir($fullPath, 0755)) {
            throw new \RuntimeException("Channel directory creation failed: {$fullPath}");
        }

        return $fullPath;
    }

    public function cleanup(bool $forceAll = false, ?string $channel = null, bool $removeEmptyDirs = true): void
    {
        if (!self::getConfig('enable')) {
            return;
        }

        $basePath = rtrim(self::getConfig('base.path'), '/');
        $channel  = $channel ?? self::getConfig('base.channel', 'app');
        $logPath  = "{$basePath}/{$channel}";

        if (!is_dir($logPath)) {
            return;
        }

        // 1. 先清理文件
        $this->deleteExpiredFiles($logPath, $forceAll);

        // 2. 后清理空目录（可选）
        if ($removeEmptyDirs) {
            $this->deleteEmptyDirectories($logPath);
        }
    }

    private function deleteExpiredFiles(string $logPath, bool $forceAll): void
    {
        $retentionDays = (int)self::getConfig('base.retention_days', 7);
        $cutoffDate    = date('Y-m-d', strtotime("-{$retentionDays} days"));

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                $fileName = $file->getFilename();

                // 解析 DD-LEVEL.log 格式
                if (preg_match('/^(\d{2})-(.+)\.log$/', $fileName, $matches)) {
                    $fileDay  = $matches[1];
                    $logLevel = strtoupper($matches[2]);
                    $fileDate = date('Y-m') . '-' . $fileDay;

                    // Debug模式保留INFO日志
                    if (self::getConfig('debug') && $logLevel === 'INFO') {
                        continue;
                    }

                    if ($forceAll || $fileDate < $cutoffDate) {
                        $this->safeUnlink($file->getRealPath());
                    }
                }
            }
        }
    }

    private function deleteEmptyDirectories(string $logPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $dir) {
            if ($dir->isDir() && $this->isEmptyDirectory($dir->getRealPath())) {
                $this->safeRmdir($dir->getRealPath());
            }
        }
    }

    private function isEmptyDirectory(string $dirPath): bool
    {
        if (!is_dir($dirPath)) {
            return false;
        }

        $dir = new \FilesystemIterator($dirPath);
        return !$dir->valid();
    }

    private function safeUnlink(string $filePath): bool
    {
        try {
            return @unlink($filePath);
        } catch (\Throwable $e) {
            error_log("Failed to delete file: {$filePath} - " . $e->getMessage());
            return false;
        }
    }

    private function safeRmdir(string $dirPath): bool
    {
        try {
            return @rmdir($dirPath);
        } catch (\Throwable $e) {
            error_log("Failed to remove directory: {$dirPath} - " . $e->getMessage());
            return false;
        }
    }

}
