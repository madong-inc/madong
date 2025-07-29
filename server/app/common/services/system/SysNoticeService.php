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

use app\common\dao\system\SysNoticeDao;
use app\common\model\system\SysNotice;
use core\abstract\BaseService;
use core\exception\handler\AdminException;
use core\uuid\UUIDGenerator;
use support\Container;


class SysNoticeService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysNoticeDao::class);
    }

    /**
     * 保存
     *
     * @param array $data
     *
     * @return \app\common\model\system\SysNotice|null
     * @throws \core\exception\handler\AdminException
     */
    public function save(array $data): ?SysNotice
    {
        try {
            return $this->transaction(function () use ($data) {
                $data['uuid'] = UUIDGenerator::generate();
                return $this->dao->save($data);
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 更新
     *
     * @param string|int $id
     * @param array      $data
     *
     * @throws \core\exception\handler\AdminException
     */
    public function update(string|int $id, array $data): void
    {
        try {
            $this->transaction(function () use ($id, $data) {
                $data['uuid'] = UUIDGenerator::generate();//更新之后变更uuid 再次推送重新发起
                $model        = $this->dao->get($id);
                $model->fill($data);
                $model->save();
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

}
