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
use app\admin\validate\platform\TenantValidate;
use app\common\scopes\global\TenantScope;
use app\common\services\platform\TenantPackageService;
use app\common\services\platform\TenantService;
use core\exception\handler\AdminException;
use core\utils\Json;
use support\Container;
use support\Request;

class TenantController extends Crud
{
    public function __construct()
    {
        /** @var TenantService $service */
        $service       = Container::make(TenantService::class);
        $this->service = $service;

        /** @var TenantValidate $validate */
        $validate       = Container::make(TenantValidate::class);
        $this->validate = $validate;
    }

    public function store(Request $request): \support\Response
    {
        try {
            $data = $this->inputFilter($request->all(), ['password', 'account', 'db_name', 'gran_subscription', 'admin_id', 'is_create_admin']);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('store')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $model = $this->service->save($data);
            if (empty($model)) {
                throw new AdminException('插入失败');
            }
            $pk = $model->getPk();
            return Json::success('ok', [$pk => $model->getData($pk)]);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    public function update(Request $request): \support\Response
    {
        try {
            $data = $this->inputFilter($request->all(), ['password', 'account', 'db_name', 'gran_subscription']);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('update')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $model = $this->service->update($data['id'], $data);
            if (empty($model)) {
                throw new AdminException('更新失败');
            }
            $pk = $model->id;
            return Json::success('ok', [$pk => $model->getData($pk)]);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    public function show(Request $request): \support\Response
    {
        try {
            $id   = $request->route->param('id');
            $data = $this->service->get($id, null, ['packages'], '', [TenantScope::class]);
            if (empty($data)) {
                throw new AdminException('数据未找到', 400);
            }
            return Json::success('ok', $data->toArray());
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
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
            $result = $this->service->destroy($data);
            return Json::success('ok', $result);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 租户选项
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function accountSets(Request $request): \support\Response
    {
        try {
            // 检查是否启用了账套模式
            $tenantEnabled          = config('tenant.enabled', false);
            $tenantAutoFirstEnabled = config('tenant.auto_select_first_tenant', true);
            //没有启用租户返回空
            if (!$tenantEnabled) {
                // 如果未启用账套模式，直接返回空数组和相关配置
                return Json::success('ok', [
                    'list'           => [],
                    'tenant_enabled' => $tenantEnabled,
                ]);
            }
            //如果是自动选择租户前端不显示选项
            if ($tenantAutoFirstEnabled) {
                // 如果未启用账套模式，直接返回空数组和相关配置
                return Json::success('ok', [
                    'list'           => [],
                    'tenant_enabled' => false,
                ]);
            }

            // 如果启用了账套模式，执行数据库查询并设置字段别名
            $list = $this->service->selectList(['enabled' => 1], '*', 0, 0, '', [], false)->setVisible(['id', 'company_name'])->toArray();
            // 手动映射字段别名
            $mappedList = array_map(function ($item) {
                return [
                    'id'        => $item['id'],
                    'tenant_id' => $item['id'],  // 设置别名
                    'name'      => $item['company_name'],
                ];
            }, $list);

            return Json::success('ok', [
                'list'           => $mappedList,
                'tenant_enabled' => $tenantEnabled,
            ]);
        } catch (\Exception $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 获取套餐ID列表
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function getTenantSubscriptionIds(Request $request): \support\Response
    {
        try {
            $id                             = $request->input('tenant_id');
            $systemTenantPackageMenuService = Container::make(TenantPackageService::class);
            $data                           = $systemTenantPackageMenuService->getSubscriptionByTenantIds($id, true);
            return Json::success('ok', $data);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 授权套餐
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function grantSubscription(Request $request): \support\Response
    {
        try {
            $data = $request->all();
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('grant_subscription')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $result = $this->service->serviceGrantSubscription($data['id'], $data['data']);
            return Json::success('ok', $result->toArray());
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 格式化下拉列表
     *
     * @param $items
     *
     * @return \support\Response
     */
    protected function formatSelect($items): \support\Response
    {
        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'label' => $item->company_name,
                'value' => $item->id,
            ];
        }
        return Json::success('ok', $formatted_items);
    }
}
