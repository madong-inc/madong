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

namespace app\admin\controller\monitor;

use app\admin\controller\Crud;
use app\admin\validate\system\SystemCrontabValidate;
use app\services\system\SystemCrontabLogService;
use app\services\system\SystemCrontabService;
use madong\exception\AdminException;
use madong\utils\Json;
use support\Container;
use support\Request;

/**
 *
 * 定时任务
 * @author Mr.April
 * @since  1.0
 */
class CrontabController extends Crud
{

    public function __construct()
    {
        parent::__construct();//调用父类构造函数
        $this->service  = Container::make(SystemCrontabService::class);
        $this->validate = Container::make(SystemCrontabValidate::class);
    }

    /**
     * 恢复任务
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function resume(Request $request): \support\Response
    {
        try {
            $data = $this->inputFilter($request->all(),['data']);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('resume')->check($data)) {
                    throw new \Exception($this->validate->getError(),-1);
                }
            }
            $this->service->resumeCrontab($data['data']);
            return Json::success('执行完成');
        } catch (\Exception $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 暂停任务
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function pause(Request $request): \support\Response
    {
        try {
            $data = $this->inputFilter($request->all(),['data']);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('pause')->check($data)) {
                    throw new \Exception($this->validate->getError(),-1);
                }
            }
            $this->service->pauseCrontab($data['data']);
            return Json::success('执行完成');
        } catch (\Exception $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 执行某个任务
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function execute(Request $request): \support\Response
    {
        try {
            $data = $this->inputFilter($request->all(),['data']);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('execute')->check($data)) {
                    throw new \Exception($this->validate->getError(),-1);
                }
            }
            $result = $this->service->runOneTask(['id'=>$data['data']]);
            if ($result['code'] == 1) {
                throw new AdminException('执行失败' . $result['log']);
            }
            return Json::success('执行完成', $result);
        } catch (\Exception $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    public function update(Request $request): Response
    {
        try {
            $id   = $request->route->param('id');
            $data = $this->inputFilter($request->all(),['month','week','day','hour','minute','second']);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('update')->check($data)) {
                    throw new Exception($this->validate->getError());
                }
            }
            $this->service->update($id, $data);
            return $this->success('ok', []);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }
}
