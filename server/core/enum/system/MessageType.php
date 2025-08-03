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

namespace core\enum\system;

enum MessageType: string
{
    // 系统类消息
    case SYSTEM_MESSAGE = 'system_message';      // 系统消息
    case ANNOUNCEMENT = 'announcement';          // 公告
    case OPERATION_LOG = 'operation_log';        // 操作日志
    case WARNING = 'warning';                    // 警告
    case NOTIFICATION = 'notification';          // 通知
    case USER_PRIVATE_MESSAGE = 'private_message'; // 用户私信
    case DATA_ALERT = 'data_alert';              // 数据警报
    case SCHEDULED_REPORT = 'scheduled_report';  // 定时报告

    // 工作流相关消息（添加wf_前缀）
    case WF_TODO_TASK = 'wf_todo_task';          // 待办任务
    case WF_WORKFLOW_MESSAGE = 'wf_workflow';    // 工作流消息
    case WF_REMINDER = 'wf_reminder';            // 提醒
    case WF_AUDIT_MESSAGE = 'wf_audit';          // 审核消息
    case WF_APPROVAL_REQUEST = 'wf_approval';    // 审批请求

    /**
     * 获取消息类型的中文标签
     */
    public function label(): string
    {
        return match ($this) {
            self::SYSTEM_MESSAGE => '系统消息',
            self::ANNOUNCEMENT => '公告',
            self::OPERATION_LOG => '操作日志',
            self::WARNING => '警告',
            self::WF_TODO_TASK => '待办任务',
            self::NOTIFICATION => '通知',
            self::USER_PRIVATE_MESSAGE => '私信',
            self::WF_WORKFLOW_MESSAGE => '工作流消息',
            self::WF_REMINDER => '提醒',
            self::WF_AUDIT_MESSAGE => '审核消息',
            self::WF_APPROVAL_REQUEST => '审批请求',
            self::DATA_ALERT => '数据警报',
            self::SCHEDULED_REPORT => '定时报告',
        };
    }

    /**
     * 获取消息类型的颜色标识
     */
    public function color(): string
    {
        return match ($this) {
            self::SYSTEM_MESSAGE => 'blue',      // 系统消息 - 蓝色
            self::ANNOUNCEMENT => 'green',       // 公告 - 绿色
            self::OPERATION_LOG => 'grey',       // 操作日志 - 灰色
            self::WARNING => 'orange',           // 警告 - 橙色
            self::WF_TODO_TASK => 'purple',      // 待办任务 - 紫色
            self::NOTIFICATION => 'teal',        // 通知 - 青绿色
            self::USER_PRIVATE_MESSAGE => 'pink',// 私信 - 粉色
            self::WF_WORKFLOW_MESSAGE => 'cyan', // 工作流消息 - 青色
            self::WF_REMINDER => 'yellow',       // 提醒 - 黄色
            self::WF_AUDIT_MESSAGE => 'indigo',  // 审核消息 - 靛蓝色
            self::WF_APPROVAL_REQUEST => 'deep-purple', // 审批请求 - 深紫色
            self::DATA_ALERT => 'red',           // 数据警报 - 红色
            self::SCHEDULED_REPORT => 'light-blue', // 定时报告 - 浅蓝色
        };
    }

    /**
     * 获取所有消息类型选项（用于下拉选择等场景）
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * 从字符串值创建枚举实例
     */
    public static function fromValue(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }

}
