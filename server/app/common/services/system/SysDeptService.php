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

namespace app\common\services\system;

use app\common\dao\system\SysDeptDao;
use core\exception\handler\AdminException;
use core\abstract\BaseService;
use support\Container;

class SysDeptService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysDeptDao::class);
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
        try {
            return $this->transaction(function () use ($data) {
                $leaders = $data['leader_id_list'] ?? [];//部门领导
                $model   = $this->dao->save($data);
                if (!empty($leaders)) {
                    $model->leader()->sync($leaders);
                }
                return $model;
            });
        } catch (\Throwable $e) {
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
                $leaders = $data['leader_id_list'] ?? [];//部门领导
                unset($data['leader_id_list']);
                $this->dao->update($id, $data);
                $systemDeptLeaderService = Container::make(SysDeptLeaderService::class);
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
                $systemDeptLeaderService = Container::make(SysDeptLeaderService::class);
                $systemDeptLeaderService->dao->delete(['dept_id' => $id]);
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

}
