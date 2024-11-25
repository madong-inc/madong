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

namespace app\services\system;

use app\dao\system\SystemDeptDao;
use app\model\tp\system\SystemDept;
use madong\basic\BaseService;
use madong\exception\AdminException;
use support\Container;
use think\facade\Db;

class SystemDeptService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemDeptDao::class);
    }

    /**
     * save
     *
     * @param array $data
     *
     * @return mixed
     */
    public function save(array $data): mixed
    {
        Db::startTrans();
        try {
            $leaders = $data['leader_id_list'] ?? [];//部门领导
            $model   = $this->dao->save($data);
            if (!empty($leaders)) {
                $model->leader()->save($leaders);
            }
            Db::commit();
            return $model;
        } catch (\Throwable $e) {
            Db::rollback();
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * update
     *
     * @param $id
     * @param $data
     *
     * @return void
     */
    public function update($id, $data): void
    {
        try {
            $this->transaction(function () use ($id, $data) {
                $this->dao->update($id, $data);
                $leaders                 = $data['leader_id_list'] ?? [];//部门领导
                $systemDeptLeaderService = Container::make(SystemDeptLeaderService::class);
                $systemDeptLeaderService->dao->delete(['dept_id' => $id]);
                if (!empty($leaders)) {
                    $insert = [];
                    foreach ($leaders as $item) {
                        $row      = [
                            'dept_id' => $id,
                            'user_id' => $item,
                        ];
                        $insert[] = $row;
                    }
                    $systemDeptLeaderService->saveAll($insert);
                }
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * get
     *
     * @param $id
     *
     * @return \app\model\tp\system\SystemDept|null
     */
    public function get($id): ?SystemDept
    {
        $model = $this->dao->get($id, ['*'], ['leader']);
        if (!empty($model)) {
            $leader = $model->getData('leader');
            $model->set('leader_id_list', array_column($leader->toArray() ?? [], 'id'));
        }
        return $model;
    }

    /**
     * destroy
     *
     * @param $id
     * @param $force
     *
     * @return void
     */
    public function destroy($id, $force): void
    {
        try {
            $this->transaction(function () use ($id, $force) {
                $res = $this->dao->count(['pid' => $id], true);
                if ($res > 0) {
                    throw new AdminException('该部门下存在子部门，请先删除子部门');
                }
                $this->dao->destroy($id);
                $systemDeptLeaderService = Container::make(SystemDeptLeaderService::class);
                $systemDeptLeaderService->dao->delete(['dept_id' => $id]);
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

}
