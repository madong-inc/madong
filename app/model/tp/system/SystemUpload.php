<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.cn
 */

namespace app\model\tp\system;

use madong\basic\BaseTpORMModel;

/**
 * 附件模型
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemUpload extends BaseTpORMModel
{

    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_upload';

    public function created(): \think\model\relation\hasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'created_by')->bind(['created_name' => 'real_name']);
    }

    public function updated(): \think\model\relation\hasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'updated_by')->bind(['updated_name' => 'real_name']);
    }

}
