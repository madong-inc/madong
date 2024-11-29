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

    protected $name = 'system_recycle_bin';

    public function operate():\Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'operate_id')->bind(['operate_name' => 'real_name']);
    }
}
