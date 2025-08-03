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

namespace app\common\queue\redis;

use app\common\model\platform\Tenant;
use app\common\services\platform\TenantService;
use app\common\services\system\SysAdminTenantService;
use core\context\TenantContext;
use core\logger\Logger;
use core\notify\enum\PushClientType;
use core\notify\Notification;
use Webman\RedisQueue\Consumer;

/**
 * 推送公告-后台
 *
 * @author Mr.April
 * @since  1.0
 */
class AdminAnnouncementPushConsumer implements Consumer
{
    public string $queue = 'admin-announcement-push';
    public string $connection = 'default';

    /**
     * 消费公告推送消息
     *
     * @param array $data 消息数据
     *
     * @return bool
     * @throws \Throwable
     */
    public function consume($data): bool
    {
        try {
            Logger::debug('公告推送开始', $data);

            // 1. 验证必要参数
            $this->validateData($data);

            // 2. 获取租户信息
            $tenantInfo = $this->getTenantInfo($data['tenant_id']);
            if (!$tenantInfo) {
                Logger::error('租户信息不存在', ['tenant_id' => $data['tenant_id']]);
                return false;
            }

            // 3. 设置租户上下文
            $this->setTenantContext($tenantInfo);

            // 4. 获取目标用户ID列表
            $adminIds = $this->getTargetAdminIds($tenantInfo->id, $data['uuid'] ?? null);

            // 5. 构建并发送通知
            $this->sendNotifications($adminIds, $data, $tenantInfo->id);

            Logger::debug("公告推送完成: {$data['title']}");
            return true;
        } catch (\Throwable $e) {
            Logger::error("公告推送失败: " . $e->getMessage(), [
                'error' => $e->getTraceAsString(),
                'data'  => $data,
            ]);
            throw $e;
        }
    }

    /**
     * 验证消息数据
     *
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        $requiredFields = ['id', 'title', 'content', 'tenant_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("缺少必要字段: {$field}");
            }
        }
    }

    /**
     * 获取租户信息
     *
     * @param int|null $tenantId
     *
     * @return Tenant|null
     */
    private function getTenantInfo(?int $tenantId): ?Tenant
    {
        if (empty($tenantId)) {
            Logger::error('租户ID不能为空');
            return null;
        }

        $tenantService = new TenantService();
        return $tenantService->get($tenantId);
    }

    /**
     * 设置租户上下文
     *
     * @param Tenant $tenantInfo
     */
    private function setTenantContext(Tenant $tenantInfo): void
    {
        TenantContext::setContext(
            $tenantInfo->id,
            $tenantInfo->code,
            $tenantInfo->isolation_mode,
            $tenantInfo->db_name,
            $tenantInfo->expired_at ?? null
        );
    }

    /**
     * 获取目标管理员ID列表
     *
     * @param int         $tenantId
     * @param string|null $uuid
     *
     * @return array
     */
    private function getTargetAdminIds(int $tenantId, ?string $uuid): array
    {
        $userService = new SysAdminTenantService();

        $query = $userService->getModel()
            ->where('tenant_id', $tenantId);

        // 如果有UUID，则排除已推送的用户
        if (!empty($uuid)) {
            $query->whereDoesntHave('message', function ($q) use ($uuid) {
                $q->where('message_uuid', $uuid);
            });
        }

        return $query->pluck('admin_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * 发送通知
     *
     * @param array $adminIds
     * @param array $data
     * @param int   $tenantId
     */
    private function sendNotifications(array $adminIds, array $data, int $tenantId): void
    {
        if (empty($adminIds)) {
            Logger::warning('没有符合条件的目标用户', ['tenant_id' => $tenantId]);
            return;
        }

        $sendData = [];
        foreach ($adminIds as $id) {
            $sendData[] = [
                'module'       => 'admin',
                'receiver_id'  => $id,
                'event'        => 'message',
                'data'         => [],
                'title'        => $data['title'],
                'content'      => $data['content'],
                'message_type' => 'message',
                'priority'     => 1,
                'related_id'   => $data['id'] ?? '',
                'expired_at'   => time() + 86400 * 7,
                'message_uuid' => $data['uuid'] ?? null,
            ];
        }

        Notification::batchSend(PushClientType::BACKEND, $tenantId, $sendData);
    }
}
