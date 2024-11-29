<?php

namespace app\model\tp\system;

use madong\basic\BaseTpORMModel;

class SystemRecycleBin extends BaseTpORMModel
{
    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'system_recycle_bin';

    public function operate(): \think\model\relation\hasOne
    {
        return $this->hasOne(SystemUser::class, 'id', 'operate_id')->bind(['operate_name' => 'real_name']);
    }
}
