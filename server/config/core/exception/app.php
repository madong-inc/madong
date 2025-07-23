<?php

return [
    'enable'  => true,
    // 异常配置
    'handler' => [
        // 不需要记录错误日志
        'dont_report' => [
            core\exception\handler\AccessDeniedHttpException::class,
            core\exception\handler\BadRequestHttpException::class,
            core\exception\handler\ForbiddenHttpException::class,
            core\exception\handler\NotFoundHttpException::class,
            core\exception\handler\ServerErrorHttpException::class,
            core\exception\handler\TooManyRequestsHttpException::class,
            core\exception\handler\UnauthorizedHttpException::class,
        ],
        // 自定义HTTP状态码
        'status'      => [
            'validate'                  => 400, // 验证器异常
            'jwt_token'                 => 401, // 认证失败
            'jwt_token_expired'         => 401, // 访问令牌过期
            'jwt_refresh_token_expired' => 402, // 刷新令牌过期
            'server_error'              => 500, // 服务器内部错误
        ],
        // 自定义响应消息
        'body'        => [
            'code' => 0,
            'msg'  => '服务器内部异常',
            'data' => null,
        ],
        /** 异常报警域名标题 */
        'domain'      => [
            'dev'  => 'dev-api.madong.tech', // 开发环境
            'test' => 'test-api.madong.tech', // 测试环境
            'pre'  => 'pre-api.madong.tech', // 预发环境
            'prod' => 'api.madong.tech',  // 生产环境
        ],
    ],

];
