<?php
/**
 * This file is part of webman.
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;
use madong\services\scheduler\event\EvalTask;
use madong\services\scheduler\event\ShellTask;
use madong\services\scheduler\event\UrlTask;
use madong\services\scheduler\event\SchedulingTask;

return [
    'debug'                    => true,
    'error_reporting'          => E_ALL,
    'default_timezone'         => 'Asia/Shanghai',
    'request_class'            => Request::class,
    'public_path'              => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path'             => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix'        => 'Controller',
    'controller_reuse'         => false,
    'model_type'               => 'laravelORM',//thinkORM||laravelORM
    'store_in_recycle_bin'     => true,//是否开启回站模式
    'exclude_from_recycle_bin' => ['system_login_log', 'system_operate_log','system_recycle_bin'],//排除的表
];
