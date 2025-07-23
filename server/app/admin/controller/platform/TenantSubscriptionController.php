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

namespace app\admin\controller\platform;

use app\admin\controller\Crud;
use app\admin\validate\platform\TenantSubscriptionValidate;
use app\common\services\platform\TenantPackageService;
use app\common\services\platform\TenantSubscriptionService;
use core\exception\handler\AdminException;
use core\utils\Json;
use support\Container;
use support\Request;

class TenantSubscriptionController extends Crud
{
    public function __construct()
    {
        parent::__construct();

        /** @var TenantSubscriptionService $service */
        $this->service  = Container::make(TenantSubscriptionService::class);
        $this->validate = Container::make(TenantSubscriptionValidate::class);
    }

    /**
     * 删除
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function destroy(Request $request): \support\Response
    {
        try {
            $id = $request->route->param('id'); // 获取路由地址 id从

            $data = $request->input('data', []);
            $data = !empty($id) && $id !== '0' ? $id : $data;
            if (empty($data)) {
                throw new AdminException('参数错误');
            }
            $result = $this->service->serviceDestroy($data);
            return Json::success('ok', $result);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 获取订阅套餐的权限IDS
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function getPackagePermissionIds(Request $request): \support\Response
    {
        try {
            $id   = $request->input('id');
            $data = $this->service->getPermissionColumns($id);
            return Json::success('ok', $data);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 获取关联租户的ids
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function getPackageTenantIds(Request $request): \support\Response
    {
        try {
            $id                             = $request->input('id');
            /** @var TenantPackageService $systemTenantPackageMenuService */
            $systemTenantPackageMenuService = Container::make(TenantPackageService::class);
            $data                           = $systemTenantPackageMenuService->getTenantIdsBySubscription($id, true);
            return Json::success('ok', $data);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 授权权限
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function grantPermission(Request $request): \support\Response
    {
        try {
            $data = $request->all();
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('grant_permission')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $result = $this->service->serviceGrantPermission($data['id'], $data['permissions']);
            return Json::success('ok', $result->toArray());
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], -1);
        }
    }

    /**
     * 关联租户
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function grantTenant(Request $request): \support\Response
    {
        try {
            $data = $request->all();
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('grant_tenant')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $result = $this->service->serviceGrantTenant($data['id'], $data['data']);
            return Json::success('ok', $result->toArray());
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

}
