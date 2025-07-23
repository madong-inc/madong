<?php

return [
    'enable'     => true,
    'token_name' => 'Authorization',
    'jwt'        => [
        /** 算法类型 HS256、HS384、HS512、RS256、RS384、RS512、ES256、ES384、Ed25519 */
        'algorithms'              => 'HS256',
        /** access令牌秘钥 */
        'access_secret_key'       => '2025d3a3LmJq',

        /** access令牌过期时间，单位：秒。默认 2 小时 */
        'access_exp'              => 7200,

        /** refresh令牌秘钥 */
        'refresh_secret_key'      => '2025KTxigxc8o50c',

        /** refresh令牌过期时间，单位：秒。默认 7 天 */
        'refresh_exp'             => 604800,

        /** refresh 令牌是否禁用，默认不禁用 false */
        'refresh_disable'         => false,

        /** 令牌签发者 */
        'iss'                     => 'madong.tech',

        /** 某个时间点后才能访问，单位秒。（如：30 表示当前时间30秒后才能使用） */
        'nbf'                     => 0,

        /** 时钟偏差冗余时间，单位秒。建议这个余地应该不大于几分钟 */
        'leeway'                  => 60,

        /** 是否允许单设备登录，默认不允许 false */
        'is_single_device'        => false,

        /** 缓存令牌时间，单位：秒。默认 7 天 */
        'cache_token_ttl'         => 604800,

        /** 缓存令牌前缀，默认 JWT:TOKEN: */
        'cache_token_pre'         => 'JWT:TOKEN:',

        /** 缓存刷新令牌前缀，默认 JWT:REFRESH_TOKEN: */
        'cache_refresh_token_pre' => 'JWT:REFRESH_TOKEN:',

        /** 用户信息模型 */
        'user_model'              => function ($uid) {
            $service = new \app\common\services\system\SysAdminService();
            $model   = $service->getAdminById($uid);
            return $model ? $model->toArray() : [];
        },
        /** 是否启用黑名单 */
        'blacklist_enabled'       => true,

        /** 黑名单宽限期（秒） */
        'blacklist_grace_period'  => 30,

        /** 黑名单REDIS前缀 */
        'cache_blacklist_pre'     => 'JWT:BLACKLIST:',
    ],
];
