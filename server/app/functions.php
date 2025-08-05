<?php
/**
 * Here is your custom functions.
 */

use app\common\model\system\SysAdmin;
use app\common\services\system\SysConfigService;
use core\jwt\JwtToken;
use support\Container;

/**
 * 获取当前认证用户信息
 *
 * @param bool $fullInfo 是否返回完整用户信息（默认只返回用户ID）
 * @param bool $refresh  是否强制从数据库刷新用户数据
 *
 * @return mixed 用户ID|array|null 返回用户ID、完整用户信息或null（未认证）
 */
function getCurrentUser(bool $fullInfo = false, bool $refresh = false): mixed
{
    $request = request();
    $rule    = $request->path();
    if (in_array($rule, ['/system/logout'])) {
        return null;
    }

    // 1. 验证请求和授权令牌
    $token = resolveAuthorizationToken();
    if ($token === null) {
        return null;
    }

    // 2. 获取当前用户ID
    $userId = JwtToken::getCurrentId();
    if ($userId === null) {
        return null;
    }

    // 3. 根据参数返回相应数据
    if ($refresh) {
        return $fullInfo ? JwtToken::getUser() : $userId;
    }

    return $fullInfo ? JwtToken::getExtend() : $userId;
}

function resolveAuthorizationToken(): ?string
{
    $request = request();
    if (empty($request)) {
        return null;
    }
    // 尝试从header获取
    $tokenName     = config('core.jwt.app.token_name', 'Authorization');
    $authorization = $request->header($tokenName);
    // 尝试从query参数获取
    if (empty($authorization) || $authorization === 'undefined') {
        $authorization = $request->get('token');
    }
    return $authorization ?: null;
}

/**
 * 提取用户的头像
 *
 * @param \app\common\model\system\SysAdmin|null $adminInfo
 *
 * @return string
 */
function getAvatarUrl(?SysAdmin $adminInfo): string
{
    /** @var TYPE_NAME $systemConfigService */
    $systemConfigService = Container::make(SysConfigService::class);

    // 获取站点配置地址
    $url          = $systemConfigService->getConfig('site_url', 'site_setting');
    $isDefaultUrl = false;

    // 处理未配置站点地址的默认逻辑
    if (empty($url)) {
        $listenUrl    = config('process.webman.listen');
        $parsedUrl    = parse_url($listenUrl);
        $port         = $parsedUrl['port'] ?? 8787; // 默认端口
        $url          = 'http://127.0.0.1:' . $port;
        $isDefaultUrl = true;
    }

    // 确保 URL 是协议型
    if (preg_match('#^https?://#i', $url)) {
        // 移除末尾斜杠并标准化路径
        $url = rtrim($url, '/');
    } else {
        // 非协议型地址保持原样拼接
        return rtrim($url, '/') . '/' . ltrim($adminInfo->getAttribute('avatar') ?? '/upload/avatar.jpg', '/');
    }

    // 获取头像路径
    $avatar = $adminInfo ? $adminInfo->getAttribute('avatar') : null;
    $avatar = $avatar ? ltrim($avatar, '/') : 'upload/avatar.jpg'; // 默认头像路径

    return $url . '/' . $avatar;
}

/**
 * 获取时间 N分钟前
 *
 * @param     $time
 * @param int $type 1 N分钟前  2 N分钟后
 *
 * @return false|string
 */
function getDateText($time = NULL, int $type = 1): bool|string
{
    if (!is_numeric($time)) {
        $time = strtotime($time);
    }
    $text = '';
    $time = $time === NULL || $time > time() ? time() : intval($time);
    $t    = time() - $time; //时间差 （秒）
    $y    = date('Y', $time) - date('Y', time());//是否跨年

    $ext = '前';
    switch ($t) {
        case $t <= 0:
            $text = '刚刚';
            break;
        case $t < 60:
            $text = $t . '秒' . $ext; // 一分钟内
            break;
        case $t < 60 * 60:
            $text = floor($t / 60) . '分钟' . $ext; //一小时内
            break;
        case $t < 60 * 60 * 24:
            $text = floor($t / (60 * 60)) . '小时' . $ext; // 一天内
            break;
        case $t < 60 * 60 * 24 * 3:
            $text = floor($time / (60 * 60 * 24)) == 1 ? '昨天 ' . date('H:i', $time) : '前天 ' . date('H:i', $time); //昨天和前天
            break;
        case $t < 60 * 60 * 24 * 30:
            $text = date('m月d日 H:i', $time); //一个月内
            break;
        case $t < 60 * 60 * 24 * 365 && $y == 0:
            $text = date('m月d日', $time); //一年内
            break;
        default:
            $text = date('Y年m月d日', $time); //一年以前
            break;
    }
    return $text;
}

/**
 * 根据字节计算大小
 *
 * @param string|int $bytes
 *
 * @return string
 */
function formatBytes(string|int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * 筛选对应前缀数组或对象-数组集合
 *
 * @param array|object $input
 * @param string       $prefix
 *
 * @return array
 */
function filterByPrefix(array|object $input, string $prefix = 'f_'): array
{
    $result = [];
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $newKey          = substr($key, strlen($prefix));
                $result[$newKey] = $value;
            }
        }
    } elseif (is_object($input)) {
        foreach ($input as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $newKey          = substr($key, strlen($prefix));
                $result[$newKey] = $value;
            }
        }
    }
    return $result;
}

/**
 * 通过给定的key 构建数据
 *
 * @param       $keys
 * @param       $data
 * @param array $skipKeys
 *
 * @return mixed
 */
function ensureKeys($keys, $data, $skipKeys = []): array
{
    $result = [];
    foreach ($keys as $key) {
        if (in_array($key, $skipKeys)) {
            continue; // 跳过指定的键
        }
        $result[$key] = array_key_exists($key, $data) ? $data[$key] : "";
    }
    return $result;
}
