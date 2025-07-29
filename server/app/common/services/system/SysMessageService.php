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

use app\common\dao\system\SysMessageDao;
use core\abstract\BaseService;
use core\enum\system\MessageStatus;
use core\notify\enum\PushClientType;
use core\notify\Notification;
use madong\helper\Arr;
use support\Container;

/**
 * @method save(array $data)
 */
class SysMessageService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SysMessageDao::class);
    }

    /**
     * 系统登录-推送消息
     *
     * @param string|int|array $id
     *
     * @return void
     * @throws \Exception
     */
    public function notifyOnFirstLoginToAll(string|int|array $id): void
    {
        try {
            // 获取历史消息记录
            $models = $this->selectList(['status' => MessageStatus::UNREAD], '*', 0, 0, '', [])->makeVisible('tenant_id')->toArray();
            if (!empty($models)) {
                foreach ($models as $value) {
                    Notification::pushOnly(PushClientType::BACKEND, 'admin', $value['tenant_id'], $value['receiver_id'], 'message', [], $value, null);
                }
            }
        } catch (\Throwable $e) {
            throw  new \Exception($e->getMessage());
        }
    }

    /**
     * 标记已读
     *
     * @param string|array $param
     *
     * @throws \Exception
     */
    public function isRead(string|array $param): void
    {
        try {
            $data         = Arr::normalize($param);
            $messageModel = $this->dao->getModel();
            $models       = $messageModel->find($data);
            foreach ($models as $model) {
                $model->status  = MessageStatus::READ;
                $model->read_at = time();
                $model->save();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 标记未读
     *
     * @param string|array $param
     *
     * @throws \Exception
     */
    public function isUnread(string|array $param): void
    {
        try {
            $data         = Arr::normalize($param);
            $messageModel = $this->dao->getModel();
            $models       = $messageModel->find($data);
            foreach ($models as $model) {
                $model->status     = MessageStatus::UNREAD;
                $model->updated_at = time();
                $model->save();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}
