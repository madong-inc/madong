<?php

namespace app\model\la\system;

use madong\basic\BaseLaORMModel;

class SystemRecycleBin extends BaseLaORMModel
{
    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $table = 'system_recycle_bin';

    protected $appends = ['operate_name'];

    /**
     * 定义访问器
     *
     * @return null
     */
    public function getOperateNameAttribute(): mixed
    {
        return $this->operate ? $this->operate->real_name : null;
    }

    public function operate():\Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'operate_id')->select(['id', 'real_name']);
    }
}
