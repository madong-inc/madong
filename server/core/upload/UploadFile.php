<?php

namespace core\upload;

use app\common\services\system\SysConfigService;
use core\exception\handler\UploadException;
use support\Container;

/**
 * 文件上传
 *
 * @author Mr.April
 * @since  1.0
 * @method static uploadFile()
 */
class UploadFile
{

    static array $allowStorage = [];

    protected static function init(): void
    {
        $configAllowStorage = config('core.upload.app.adapter_classes');
        self::$allowStorage = array_unique(array_merge([
            'local',
            'oss',
            'cos',
            'qiniu',
            's3',
        ], array_keys($configAllowStorage)));
    }

    /**
     * 获取配置信息
     *
     * @param string $name
     *
     * @return array|null
     */
    public static function getConfig(string $name = ''): ?array
    {
        /** @var  SysConfigService $systemConfigService */
        $systemConfigService = Container::make(SysConfigService::class);
        $config              = $systemConfigService->getConfigContentValue($name);
        return $config ?? [];
    }

    /**
     * 获取默认配置
     *
     * @return array
     */
    public static function getDefaultConfig(): array
    {
        $systemConfigService = Container::make(SysConfigService::class);
        $basicConfig         = $systemConfigService->getConfig('basic_upload_setting');
        if (empty($basicConfig)) {
            return [
                'mode'         => 'local',
                'single_limit' => 1024,
                'total_limit'  => 1024,
                'nums'         => 1,
                'include'      => ['png'],
                'exclude'      => ['mp4'],
            ];
        }
        return $basicConfig;
    }

    public static function disk(string|null $storage = null, bool $is_file_upload = true): UploadFileInterface
    {
        self::init();
        $defaultConfig = self::getDefaultConfig();
        if (empty($storage)) {
            $adapter       = $defaultConfig['mode'];
            $adapterConfig = self::getConfig($adapter);
        } else {
            $adapter       = $storage;
            $adapterConfig = self::getConfig($storage);
        }
        if (!in_array($adapter, self::$allowStorage)) {
            throw new UploadException("不支持的存储类型:" . $adapter);
        }
        $config = array_merge($defaultConfig, $adapterConfig, ['_is_file_upload' => $is_file_upload]);
        $handle = config('core.upload.app.adapter_classes.' . $adapter);
        if (!$handle) {
            throw new UploadException("未找到适配器处理器:" . $handle);
        }
        return new $handle($config);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return static::disk()->{$name}(...$arguments);
    }

}
