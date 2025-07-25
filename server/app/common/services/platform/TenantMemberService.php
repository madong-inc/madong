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

namespace app\common\services\platform;

use app\common\dao\system\SysAdminDao;
use app\common\model\system\SysAdmin;
use app\common\scopes\global\TenantScope;
use app\common\services\system\SysAdminTenantService;
use core\abstract\BaseService;
use core\context\TenantContext;
use core\enum\system\PolicyPrefix;
use core\exception\handler\AdminException;
use support\Container;

/**
 * 租户会员管理
 *
 * @author Mr.April
 * @since  1.0
 * @method getTenantMemberList(mixed $where, mixed $field, mixed $page, mixed $limit, mixed $order, array $array, false $false)
 */
class TenantMemberService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysAdminDao::class);
    }

    /**
     * save
     *
     * @param array $data
     *
     * @return SysAdmin|null
     * @throws \core\exception\handler\AdminException
     */
    public function save(array $data): SysAdmin|null
    {
        try {
            return $this->transaction(function () use ($data) {
                //1.0 添加用户数据
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                unset($data['role_id_list'], $data['post_id_list'], $data['dept_id']);
                //中间表模型不会自动创建时间戳手动添加
                $data['created_at']      = time();
                $data['updated_at']      = time();
                $data['is_tenant_admin'] = 1;//标识租户管理员
                $model                   = $this->dao->save($data);
                //创建关联租户
                $adminTenant = [
                    'admin_id'   => $model->id,
                    'tenant_id'  => TenantContext::getTenantId(),
                    'is_super'   => 1,//租户管理员
                    'is_default' => 1,
                    'priority'   => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
                /** @var SysAdminTenantService $adminService */
                $adminService = Container::make(SysAdminTenantService::class);
                $adminService->dao->save($adminTenant);
                //同步casbin 关联表
                $userCasbin = PolicyPrefix::USER->value . $model->id;
                $model->casbin()->sync([$userCasbin]);
                return $model;

            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 编辑
     *
     * @param int   $id
     * @param array $data
     *
     * @return \app\common\model\system\SysAdmin|null
     * @throws \core\exception\handler\AdminException
     */
    public function update(int $id, array $data): ?SysAdmin
    {
        try {
            return $this->transaction(function () use ($id, $data) {
                // 1.0 更新用户基础数据
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                //中间表模型不会自动创建时间戳手动添加
                $data['updated_at'] = time();
                $data['created_at'] = time();

                // 更安全的查询方式
                $model = $this->dao->getModel()
                    ->withoutGlobalScope(TenantScope::class)
                    ->findOrFail($id);

                // 更安全的属性填充
                $model->fill($data);
                $model->save();
                return $model;
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

}
