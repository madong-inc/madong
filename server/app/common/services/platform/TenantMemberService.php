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
use app\common\enum\system\PolicyPrefix;
use app\common\model\system\SysAdmin;
use app\common\scopes\global\TenantScope;
use app\common\services\system\SysAdminTenantService;
use madong\admin\abstract\BaseService;
use madong\admin\context\TenantContext;
use madong\admin\ex\AdminException;
use support\Container;

/**
 * 租户会员管理
 *
 * @author Mr.April
 * @since  1.0
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
     */
    public function save(array $data): SysAdmin|null
    {
        try {
            return $this->transaction(function () use ($data) {
                //1.0 添加用户数据
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                unset($data['role_id_list'], $data['post_id_list'], $data['dept_id']);
                //中间表模型不会自动创建时间戳手动添加
                $data['created_at'] = time();
                $data['updated_at'] = time();
                $model              = $this->dao->save($data);
                //创建关联租户
                $adminTenant = [
                    'admin_id'   => $model->id,
                    'tenant_id'  => TenantContext::getTenantId(),
                    'is_super'   => 2,//普通用户
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
     */
    public function update(int $id, array $data): ?SysAdmin
    {
        try {
            return $this->transaction(function () use ($id, $data) {
                // 1.0 更新用户基础数据
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                $roles = $data['role_id_list'] ?? [];
                $posts = $data['post_id_list'] ?? [];
                $depts = array_filter(explode(',', $data['dept_id'] ?? ''));
                unset($data['role_id_list'], $data['post_id_list'], $data['dept_id']);
                //中间表模型不会自动创建时间戳手动添加
                $data['updated_at'] = time();

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
