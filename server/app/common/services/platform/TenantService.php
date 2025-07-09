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

use app\common\dao\platform\TenantDao;
use app\common\enum\system\PolicyPrefix;
use app\common\model\platform\Tenant;
use app\common\model\system\SysAdmin;
use app\common\scopes\global\TenantScope;
use app\common\services\system\SysAdminService;
use app\common\services\system\SysAdminTenantService;
use InvalidArgumentException;
use madong\admin\abstract\BaseService;
use madong\helper\Arr;
use madong\helper\PropertyCopier;
use madong\admin\services\uuid\UUIDGenerator;
use support\Container;

class TenantService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(TenantDao::class);
    }

    /**
     * 添加saas账套
     *
     * @param array $data
     *
     * @return mixed
     * @throws \Throwable
     */
    public function save(array $data): mixed
    {
        return $this->transaction(function () use ($data) {
            try {
                //1.0 库分离模式验证数据库
                if ((int)$data['isolation_mode'] == 2) {
                    /**@var DbSettingService $systemDbSettingService */
                    $systemDbSettingService = Container::make(DbSettingService::class);
                    $db                     = $systemDbSettingService->get(['database' => $data['db_name']]);
                    if (empty($db)) {
                        throw  new \Exception('数据源=' . $data['name'] . '不存在');
                    }
                    $data['db_name'] = $db->database;
                }

                $data['code'] = UUIDGenerator::generate('custom', 6);//自动生成uuid
                $userInfo     = [
                    'password'     => password_hash($data['password'], PASSWORD_DEFAULT),
                    'user_name'    => $data['account'] ?? '',
                    'real_name'    => $data['contact_person'] ?? '',
                    'nick_name'    => $data['contact_person'] ?? '',
                    'mobile_phone' => $data['contact_phone'] ?? '',
                    'is_super'     => 0,//非管理员
                    'avatar'       => '/upload/default.png',
                    'enabled'      => 1,
                    'created_at'   => time(),
                ];
                unset($data['password'], $data['account'], $data['db_id']);
                if (!isset($data['type'])) {
                    $data['type'] = 1;
                }
                //2 添加租户信息
                $model = $this->dao->save($data);

                //3 添加用户信息
                $service = new SysAdminService();
                /** @var  SysAdmin $userModel */
                $userModel            = $service->dao->save($userInfo);
                $casbinUserIdentifier = PolicyPrefix::USER->value . strval($userModel->id);
                $userModel->casbin()->sync([$casbinUserIdentifier]);
                //添加租户授权套餐
                $model->packages()->sync(Arr::normalize($data['gran_subscription']));
                $userTenantData = [
                    'admin_id'   => $userModel->id,
                    'tenant_id'  => $model->id,
                    'is_super'   => 1,//管理员账号
                    'is_default' => 1,
                    'priority'   => -1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
                //4 添加用户关联租户并设置管理员
                $adminTenantService = new SysAdminTenantService();
                $adminTenantService->dao->save($userTenantData);
                return $model;
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        });
    }

    /**
     * 更新
     *
     * @param string $id
     * @param array  $data
     *
     * @return mixed
     * @throws \Throwable
     */
    public function update(string $id, array $data): mixed
    {
        return $this->transaction(function () use ($data) {
            try {

                if ((int)$data['isolation_mode'] == 2) {
                    /**@var DbSettingService $systemDbSettingService */
                    $systemDbSettingService = Container::make(DbSettingService::class);
                    $db                     = $systemDbSettingService->get(['database' => $data['db_name']]);
                    if (empty($db)) {
                        throw  new \Exception('数据源=' . $data['name'] . '不存在');
                    }
                    $data['db_name'] = $db->database;
                }
                if (!isset($data['code'])) {
                    $data['code'] = UUIDGenerator::generate('custom', 6);//自动生成uuid
                }

                unset($data['password'], $data['account']);
                if (!isset($data['type'])) {
                    $data['type'] = 1;
                }
                $model = $this->dao->get($data['id']);
                PropertyCopier::copyProperties((object)$data, $model);
                //添加租户授权套餐
                $model->packages()->sync(Arr::normalize($data['gran_subscription']));
                $model->save();
                return $model;
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        });
    }

    /**
     * 删除租户and删除关联数据
     *
     * @param array|int|string $id
     *
     * @return mixed
     * @throws \Throwable
     */
    public function destroy(array|int|string $id): mixed
    {
        return $this->transaction(function () use ($id) {
            $data       = is_array($id) ? $id : explode(',', $id);
            $deletedIds = [];
            foreach ($data as $i) {
                $item = $this->get($i);
                if (!$item) {
                    continue; // 如果找不到项，跳过
                }
                $item->delete();
                $item->packages()->detach();
                $item->admins()->detach();
                $primaryKey   = $item->getPk();
                $deletedIds[] = $item->{$primaryKey};
            }
            return $deletedIds;
        });
    }

    /**
     * 授权套餐
     *
     * @throws \Exception|\Throwable
     */
    public function serviceGrantSubscription(string|int $id, array $data = []): ?Tenant
    {
        try {
            return $this->transaction(function () use ($id, $data) {
                $model = $this->dao->getModel()->withoutGlobalScopes([TenantScope::class])->findOrFail($id);
                $model->packages()->sync($data);
                return $model;
            });
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

}
