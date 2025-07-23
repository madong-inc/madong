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

use core\abstract\BaseModel;

/**
 * 系统公告
 *
 * @author Mr.April
 * @since  1.0
 */
class SysMessage extends BaseModel
{

    protected $table = 'sys_message';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    protected $appends = ['created_date', 'updated_date'];

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
        'read_at',
        'created_at',
        'expired_at',
    ];

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
