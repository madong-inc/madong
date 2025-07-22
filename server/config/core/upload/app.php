<?php

use core\upload\storage\Cos;
use core\upload\storage\Local;
use core\upload\storage\Oss;
use core\upload\storage\Qiniu;
use core\upload\storage\S3;

return [
    'enable'          => true,
    'debug'           => config('app.debug'),
    'cdn_url'         => '',
    'cdn_url_params'  => '',
    'default_avatar'  => '/upload/avatar.jpeg',
    'config_key'      => [
        'local' => '',
        'oss'   => '',
        'cos'   => '',
        'qiniu' => '',
        's3'    => '',
    ],
    'adapter_classes' => [
        'local' => Local::class,
        'oss'   => Oss::class,
        'cos'   => Cos::class,
        'qiniu' => Qiniu::class,
        's3'    => S3::class,
    ],
];
