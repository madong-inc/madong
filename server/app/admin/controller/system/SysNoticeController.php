<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.tech
 */

namespace app\admin\controller\system;

use app\admin\controller\Crud;
use app\admin\validate\system\SysNoticeValidate;
use app\common\services\system\SysNoticeService;
use core\utils\Json;
use support\Container;
use support\Request;
use Webman\RedisQueue\Client;

class SysNoticeController extends Crud
{
    public function __construct()
    {
        parent::__construct();
        $this->validate = Container::make(SysNoticeValidate::class);
        $this->service  = Container::make(SysNoticeService::class);
    }

    /**
     * 发布公告
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function publish(Request $request): \support\Response
    {
        try {
            $id = $request->input('id', null);
            //推送公告
            $model = $this->service->get($id);
            //推送消息
            $queue = 'admin-announcement-push';
            Client::send($queue, $model->makeVisible('tenant_id')->toArray(), 0);
            return Json::success('ok', []);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }
}
