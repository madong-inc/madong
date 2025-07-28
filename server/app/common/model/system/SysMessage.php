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

namespace app\common\model\system;

use Carbon\Carbon;
use core\abstract\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 系统公告
 *
 * @author Mr.April
 * @since  1.0
 */
class SysMessage extends BaseModel
{

    /**
     * 启用软删除
     */
    use SoftDeletes;

    protected $table = 'sys_message';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    protected $appends = ['created_date', 'updated_date', 'read_date'];

    protected $fillable = [
        'id',
        'tenant_id',
        'type',
        'title',
        'content',
        'sender_id',
        'receiver_id',
        'status',
        'priority',
        'channel',
        'related_id',
        'related_type',
        'action_url',
        'action_params',
        'message_uuid',
        'read_at',
        'created_at',
        'expired_at',
    ];

    /**
     * 追加已读时间
     *
     * @return string|null
     */
    public function getReadDateAttribute(): ?string
    {
        if ($this->getAttribute('read_at')) {
            try {
                $timestamp = $this->getRawOriginal('read_at');
                if (empty($timestamp)) {
                    return null;
                }
                $carbonInstance = Carbon::createFromTimestamp($timestamp);
                return $carbonInstance->setTimezone(config('app.default_timezone'))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * 关联发送用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sender(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SysAdmin::class, 'id', 'sender_id')->select(['id', 'real_name', 'user_name', 'dept_id']);
    }

}
