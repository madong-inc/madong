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

use Webman\Route;

Route::group(function () {
    Route::get('/terminal', [\plugin\cmdr\app\controller\Index::class, 'index'])->name('开发平台.Web终端.执行');
    Route::put('/terminal/config', [\plugin\cmdr\app\controller\Index::class, 'updateConfig'])->name('开发平台.Web终端.更新包管理');
});
