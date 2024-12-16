<?php

namespace app\services\system;

use app\dao\system\SystemCrontabDao;
use madong\basic\BaseService;
use madong\exception\AdminException;
use madong\services\scheduler\Client;
use madong\services\scheduler\event\EventBootstrap;
use support\Container;

/**
 * 定时任务
 *
 * @author Mr.April
 * @since  1.0
 */
class SystemCrontabService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemCrontabDao::class);
    }

    /**
     * 获取列表
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     * @param string $order
     * @param array  $with
     * @param bool   $search
     *
     * @return \madong\trait\Model
     */
    public function selectList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false)
    {
        $result = parent::selectList($where, $field, $page, $limit, $order, [], $search);
        //兼容处理
        if (config('app.model_type', 'thinkORM') === 'laravelORM') {
            if (!empty($result)) {
                foreach ($result as $item) {
                    // 设置最后运行时间
                    $item->last_running_time = $item->getData('last_running_time')
                        ? date('Y-m-d H:i:s', $item->getData('last_running_time'))
                        : null;
                    // 获取相关日志并设置到项中
                    $item->log = $item->getData('log');
                }
            }
            return $result;
        }

        $systemCrontabLogService = Container::make(SystemCrontabLogService::class);
        if (!empty($result)) {
            foreach ($result as $item) {
                $item->rule_name .= '';
                $item->set('last_running_time', date('Y-m-d H:i:s', $item->getData('last_running_time')));
                $item->logs = $systemCrontabLogService->getModel()
                    ->where(['crontab_id' => $item->id])
                    ->order('create_time', 'desc')
                    ->find();
            }
        }
        return $result;
    }

    /**
     * 添加定时任务
     *
     * @param array $data
     *
     * @return mixed
     */
    public function save(array $data): mixed
    {
        try {
            return $this->transaction(function () use ($data) {
                $title      = $data['title'] ?? '';
                $type       = $data['type'] ?? '';
                $target     = $data['target'] ?? '';
                $status     = $data['enabled'] ?? 1;
                $singleton  = $data['singleton'] ?? 1;
                $task_cycle = $data['task_cycle'] ?? '';
                $month      = $data['month'] ?? '';
                $week       = $data['week'] ?? '';
                $day        = $data['day'] ?? '';
                $hour       = $data['hour'] ?? '';
                $minute     = $data['minute'] ?? '';
                $second     = $data['second'] ?? '';
                $check_arr  = [
                    'second' => function () use ($second) {
                        //注意，这里秒数必须是60的因数，由于workerman/crontab解析问题，秒级任务的话，每一分钟他会直接重置一次计时器
                        $second = (int)$second;
                        if (60 % $second !== 0) {
                            throw new \Exception('秒级任务必须是60的因数');
                        }
                    },
                    'minute' => function () use ($minute) {
                        //  Validate::make()->isRequire("请输入执行分钟")->isInteger('分钟必须为整数')->isElt(59, "分钟不能大于59")->check($minute);
                    },
                    'hour'   => function () use ($hour) {
                        //    Validate::make()->isRequire("请输入执行小时")->isInteger('小时必须为整数')->isElt(59, "小时不能大于59")->check($hour);
                    },
                    'day'    => function () use ($day) {
                        //  Validate::make()->isRequire("请输入执行天数")->isInteger('天数必须为整数')->isElt(31, "天数不能大于31")->check($day);
                    },
                    'week'   => function () use ($week) {
                        //   Validate::make()->isRequire("请输入星期几执行")->isInteger('星期几必须为整数')->isElt(6, "星期几不能大于6")->check($week);
                    },
                    'month'  => function () use ($month) {
                        //   Validate::make()->isRequire("请输入执行月份")->isInteger('月份必须为整数')->isElt(12, "月份不能大于12")->check($month);
                    },
                ];
                switch ($task_cycle) {
                    case 1:
                        $check_arr['minute']();
                        $check_arr['hour']();
                        $rule = "{$minute} {$hour} * * *";
                        break;
                    case 2:
                        $check_arr['minute']();
                        $rule = "{$minute} * * * *";
                        break;
                    case 3:
                        $check_arr['minute']();
                        $check_arr['hour']();
                        $rule = "{$minute} */{$hour} * * *";
                        break;
                    case 4:
                        $check_arr['minute']();
                        $rule = "*/{$minute} * * * *";
                        break;
                    case 5:
                        $check_arr['second']();
                        $rule = "*/{$second} * * * * *";
                        break;
                    case 6:
                        $check_arr['week']();
                        $check_arr['hour']();
                        $check_arr['minute']();
                        $rule = "{$minute} {$hour} * * {$week}";
                        break;
                    case 7:
                        $check_arr['day']();
                        $check_arr['hour']();
                        $check_arr['minute']();
                        $rule = "{$minute} {$hour} {$day} * *";
                        break;
                    case 8:
                        $check_arr['month']();
                        $check_arr['day']();
                        $check_arr['hour']();
                        $check_arr['minute']();
                        $rule = "{$minute} {$hour} {$day} {$month} *";
                        break;
                    default:
                        throw new  \Exception("任务周期不正确");
                }
                //更新到数据库
                $model = $this->dao->save([
                    'title'      => $title,
                    'type'       => $type,
                    'rule'       => $rule,
                    'target'     => $target,
                    'enabled'    => $status,
                    'singleton'  => $singleton,
                    'task_cycle' => $task_cycle,
                    'cycle_rule' => json_encode([
                        'month' => $month, 'week' => $week, 'day' => $day, 'hour' => $hour, 'minute' => $minute, 'second' => $second,
                    ]),
                ]);
                //添加定时任务重启服务
                if (!empty($model)) {
                    $pk = $model->getPk();
                    $this->requestData($model->getData($pk));
                }
                return $model;
            });
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 更新定时任务
     *
     * @param       $id
     * @param array $data
     */
    public function update($id, array $data): void
    {
        try {
            $this->transaction(function () use ($id, $data) {
                $title      = $data['title'] ?? '';
                $type       = $data['type'] ?? '';
                $target     = $data['target'] ?? '';
                $status     = $data['enabled'] ?? 1;
                $singleton  = $data['singleton'] ?? 1;
                $task_cycle = $data['task_cycle'] ?? '';
                $month      = $data['month'] ?? '';
                $week       = $data['week'] ?? '';
                $day        = $data['day'] ?? '';
                $hour       = $data['hour'] ?? '';
                $minute     = $data['minute'] ?? '';
                $second     = $data['second'] ?? '';
                $check_arr  = [
                    'second' => function () use ($second) {
                        //注意，这里秒数必须是60的因数，由于workerman/crontab解析问题，秒级任务的话，每一分钟他会直接重置一次计时器
                        $second = (int)$second;
                        if (60 % $second !== 0) {
                            throw new \Exception('秒级任务必须是60的因数');
                        }
                    },
                    'minute' => function () use ($minute) {
                        //  Validate::make()->isRequire("请输入执行分钟")->isInteger('分钟必须为整数')->isElt(59, "分钟不能大于59")->check($minute);
                    },
                    'hour'   => function () use ($hour) {
                        //    Validate::make()->isRequire("请输入执行小时")->isInteger('小时必须为整数')->isElt(59, "小时不能大于59")->check($hour);
                    },
                    'day'    => function () use ($day) {
                        //  Validate::make()->isRequire("请输入执行天数")->isInteger('天数必须为整数')->isElt(31, "天数不能大于31")->check($day);
                    },
                    'week'   => function () use ($week) {
                        //   Validate::make()->isRequire("请输入星期几执行")->isInteger('星期几必须为整数')->isElt(6, "星期几不能大于6")->check($week);
                    },
                    'month'  => function () use ($month) {
                        //   Validate::make()->isRequire("请输入执行月份")->isInteger('月份必须为整数')->isElt(12, "月份不能大于12")->check($month);
                    },
                ];
                switch ($task_cycle) {
                    case 1:
                        $check_arr['minute']();
                        $check_arr['hour']();
                        $rule = "{$minute} {$hour} * * *";
                        break;
                    case 2:
                        $check_arr['minute']();
                        $rule = "{$minute} * * * *";
                        break;
                    case 3:
                        $check_arr['minute']();
                        $check_arr['hour']();
                        $rule = "{$minute} */{$hour} * * *";
                        break;
                    case 4:
                        $check_arr['minute']();
                        $rule = "*/{$minute} * * * *";
                        break;
                    case 5:
                        $check_arr['second']();
                        $rule = "*/{$second} * * * * *";
                        break;
                    case 6:
                        $check_arr['week']();
                        $check_arr['hour']();
                        $check_arr['minute']();
                        $rule = "{$minute} {$hour} * * {$week}";
                        break;
                    case 7:
                        $check_arr['day']();
                        $check_arr['hour']();
                        $check_arr['minute']();
                        $rule = "{$minute} {$hour} {$day} * *";
                        break;
                    case 8:
                        $check_arr['month']();
                        $check_arr['day']();
                        $check_arr['hour']();
                        $check_arr['minute']();
                        $rule = "{$minute} {$hour} {$day} {$month} *";
                        break;
                    default:
                        throw new  \Exception("任务周期不正确");
                }
                //更新到数据库
                $this->dao->update($id, [
                    'title'      => $title,
                    'type'       => $type,
                    'rule'       => $rule,
                    'target'     => $target,
                    'enabled'    => $status,
                    'singleton'  => $singleton,
                    'task_cycle' => $task_cycle,
                    'cycle_rule' => json_encode([
                        'month' => $month, 'week' => $week, 'day' => $day, 'hour' => $hour, 'minute' => $minute, 'second' => $second,
                    ]),
                ]);
                //更新之后重启服务
                $this->requestData($id);

            });

        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 定时任务删除
     *
     * @param array|int|string $id
     *
     * @return bool
     */
    public function destroy(array|int|string $id): bool
    {
        try {
            return $this->transaction(function () use ($id) {
                //1.0 先关闭再删除,避免删了后直接连不上服务的情况出现
                $this->dao->update([['id', 'in', $id]], ['enabled' => 0]);
                //2.0 重启任务
                $this->requestData($id);
                //3.0 删除定时任务跟日志数据
                $this->dao->destroy($id);
                $systemCrontabLogService = Container::make(SystemCrontabLogService::class);
                $systemCrontabLogService->delete(['crontab_id' => $id]);
                return true;
            });
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 恢复任务
     *
     * @param string|int|array $data
     */
    public function resumeCrontab(string|int|array $data): void
    {
        try {
            $this->transaction(function () use ($data) {
                $this->dao->update([['id', 'in', $data]], ['enabled' => 1]);//更改启用
                $result = $this->requestData($data);
                if (!$result) {
                    throw new AdminException('恢复失败');
                }
            });
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     *暂停任务
     *
     * @param string|int|array $data
     */
    public function pauseCrontab(string|int|array $data): void
    {
        try {
            $this->transaction(function () use ($data) {
                var_dump($data);
                $this->dao->update([['id', 'in', $data]], ['enabled' => 0]);//更改禁用
                $result = $this->requestData($data);
                if (!$result) {
                    throw new AdminException('重启失败');
                }
            });
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 获取所有任务
     *
     * @param string $field
     *
     * @return array
     */
    public function getTaskAll(string $field = '*'): array
    {
        return $this->dao->selectList(['enabled' => 1], $field, 0, 0, '', [], true)->toArray();
    }

    /**
     * 传入ID 获取指定的一条任务
     *
     * @param $id
     *
     * @return mixed
     */
    public function getTask($id): mixed
    {
        return $this->dao->get($id);
    }

    /**
     * 运行单个任务
     *
     * @param $id
     *
     * @return array
     */
    public function runOneTask($id): array
    {
        /**  @var $task_handle EventBootstrap[] */
        $task_handle = config('task.task_handle');
        $crontab     = $this->dao->get($id);
        $start_time  = microtime(true);
        try {
            if (empty($crontab)) {
                throw new \Exception('执行任务失败, 任务不存在');
            }
            if (!isset($task_handle[$crontab->getData('type')])) {
                throw new \Exception('执行任务失败, 任务类型错误: ');
            }
            $result_data = $task_handle[$crontab['type']]::parse($crontab);
            // 记录执行信息
            $crontab->last_running_time = $start_time;
            $crontab->inc('running_times', 1);
            if ($crontab->singleton == 0) {
                // 单次任务 直接停用
                $crontab->status = 0;
            }
            if (!$crontab->save()) {
                throw new \Exception('记录保存失败');
            }
            $end_time = microtime(true);
            if (strlen($result_data['log']) > 300) {
                $result_data['log'] = mb_substr($result_data['log'], 0, 300) . '...';
            }
            // 写入执行日志
            $installData       = [
                'crontab_id'   => $crontab['id'] ?? '',
                'target'       => $crontab['target'] ?? '',
                'log'          => $result_data['log'] ?? '--',
                'return_code'  => $result_data['code'] ?? 1,
                'running_time' => round($end_time - $start_time, 6),
                'create_time'  => $start_time,
            ];
            $crontabLogService = Container::make(SystemCrontabLogService::class);
            $crontabLogModel   = $crontabLogService->dao->save($installData);
            if (empty($crontabLogModel)) {
                throw new \Exception('日志记录保存失败');
            }
        } catch (\Exception $e) {
            return ['code' => 1, 'log' => $e->getMessage() . '---任务id: ' . $id];
        }
        return [
            'code'       => $installData['return_code'],
            'log'        => $installData['log'],
            'crontab_id' => $installData['crontab_id'],
        ];
    }

    /**
     * 重启任务
     *
     * @param int|string|array $id_str int|string 需要重启的任务id,多个id用，拼接，例：1,2,3,4,5
     *
     * @return bool
     * @throws \Exception
     */
    public function requestData(int|string|array $id_str): bool
    {
        // 如果是数组，使用 implode 连接为字符串；如果是单个值，直接使用
        $ids = is_array($id_str) ? implode(',', $id_str) : $id_str;
        // 重启任务
        $param = ['method' => 'crontabReload', 'args' => ['id' => $ids]];
        return Client::request($param);
    }
}
