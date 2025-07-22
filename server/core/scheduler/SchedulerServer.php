<?php

namespace core\scheduler;

use app\common\services\system\SysCrontabService;
use support\Container;
use Webman\RedisQueue\Client as RedisClient;
use WebmanTech\SymfonyLock\Locker;
use Workerman\Connection\TcpConnection;
use Workerman\Crontab\Crontab;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 该任务调度进程服务必须安装以下扩展组件
 * 1.定时任务 composer require workerman/crontab
 * 2.Redis   composer require webman/redis-queue illuminate/redis
 * 3.业务锁  composer require webman-tech/symfony-lock
 * 4.env组件 composer require vlucas/phpdotenv
 * @method getAllTask() 获取所有任务
 * @method getTask($id) 获取单个任务
 * @method writeRunLog($insert_data = []) 写入运行日志
 * @method updateTaskRunState($id, $last_running_time) 更新任务状态
 */
class SchedulerServer
{
    /**
     * 进程
     *
     * @var \Workerman\Worker
     */
    protected \Workerman\Worker $worker;

    /**
     * Redis
     *
     * @var \Workerman\RedisQueue\Client|null
     */
    protected \Workerman\RedisQueue\Client|null $redis;

    /**
     * 调试模式
     *
     * @var bool
     */
    protected bool $debug = false;
    /**
     * 是否运行定时任务呢
     *
     * @var bool
     */
    protected bool $enabled = false;

    /**
     * 日志
     *
     * @var bool
     */
    protected bool $writeLog = true;

    /**
     * 配置
     *
     * @var array
     */
    protected array $config = [];

    /**
     * 任务进程池
     *
     * @var Crontab[] array
     */
    private array $crontabPool = [];

    /**
     * @var \Workerman\RedisQueue\Client|null 订阅实例
     */
    protected \Workerman\RedisQueue\Client|null $subscribeClient;

    /**
     * @var \Workerman\RedisQueue\Client|null 通知实例
     */
    protected \Workerman\RedisQueue\Client|null $publishClient;

    /**
     * @param Worker $worker
     *
     * @return void
     * 进程启动
     */
    public function onWorkerStart(Worker $worker)
    {
        $config         = config('core.scheduler.app');
        $this->debug    = $config['debug'] ?? true;
        $this->writeLog = $config['write_log'] ?? true;
        $this->enabled  = $config['enable'] ?? false;

        // 没有安装跳出运行
        if (!is_file(base_path() . '/install.lock')) {
            return false;
        }

        if (!$this->enabled) {
            $this->writeln('定时任务未开启，如需开启，请修改配置 .env APP_TASK_ENABLED=true 或者config\\core\\scheduler\\app.php. enable = true ');
            return false;
        }
        $this->writeln("定时任务消息通道：{$config['listen']}，请注意端口是否冲突，");
        $this->writeln("如果需要修改端口，请修改 config\\core\\scheduler\\app.php,process.php 两个文件");
        $this->config = $config;
        $this->worker = $worker;
        //订阅事件
        $this->subscribeEvent();
        //初始化任务
        $this->crontabInit();
    }

    /**
     * 订阅事件
     */
    private function subscribeEvent(): void
    {

        //创建发布实例
        if (empty($this->publishClient)) {
            $this->publishClient = $this->redisCreate();
            $this->writeln('启用心跳');
            Timer::add(30, function () {//保持链接活跃
                $this->writeln('发送心跳');
                $this->publishClient->send('ping', ['name' => 'ping', 'age' => 1]);
            });
        }

        // 创建订阅实例
        if (empty($this->subscribeClient)) {
            $this->subscribeClient = $this->redisCreate();
        }

        //订阅一个心跳链接
        $this->subscribeClient->subscribe('ping', function ($data) {
            $this->writeln('收到心跳：' . json_encode($data, 256));
        });

        //订阅任务改变事件
        $this->subscribeClient->subscribe('change_contrab', function ($data) {
            $args = $data['args'] ?? '';
            $this->crontabReload($args);
        });
    }

    /**
     * Redis 初始化
     *
     * @return false|\Webman\RedisQueue\Client|\Workerman\RedisQueue\Client
     */
    function redisCreate(): false|RedisClient|\Workerman\RedisQueue\Client
    {
        $connection_name = 'default';
        try {
            $redis = RedisClient::connection($connection_name);
        } catch (\Exception $e) {
            $this->writeln('Redis初始化失败');
            return false;
        }
        return $redis;
    }

    /**
     * @param TcpConnection $connection
     * @param               $data
     *
     * @return void
     */
    public function onMessage(TcpConnection $connection, $data): void
    {
        $data   = json_decode($data, true);
        $method = $data['method'] ?? '';
        $args   = $data['args'] ?? '';
        // 这里只有保留一个重启方法,对任务进行任何操作,直接调用重启解决
        if (!in_array($method, ['crontabReload'])) {
            $connection->send(json_encode(['code' => 400, 'msg' => "{$method} is not found"]));
            return;
        }
        $this->writeln('发送重启通知');
        //通知所有进程该任务进行重启
        $this->publishClient->send('change_contrab', ['method' => 'crontabReload', 'args' => $args]);
        $connection->send(json_encode(['code' => 200, 'msg' => "ok"]));
    }

    /**
     * 重启定时任务
     *
     * @param array $param
     */
    private function crontabReload(array $param): void
    {
        $ids = explode(',', (string)($param['id'] ?? ''));
        foreach ($ids as $id) {
            if (isset($this->crontabPool[$id])) {
                $this->writeln('销毁定时器，ID：' . $id);
                $this->crontabPool[$id]['crontab']->destroy();
                unset($this->crontabPool[$id]);
            }
            $this->crontabRun($id);
        }
    }

    /**
     * 初始化定时任务
     *
     * @return void
     */
    private function crontabInit(): void
    {
        /**
         * 定时任务服务类
         */
        $systemCrontabService = Container::make(SysCrontabService::class);
        $data                 = $systemCrontabService->getTaskAll('id');
        $this->writeln('定时器任务数：' . count($data));
        if (!empty($data)) {
            foreach ($data as $item) {
                $this->crontabRun($item['id']);
            }
        }
    }

    /**
     * 创建定时器
     *
     * @param string|int $id
     */
    private function crontabRun(string|int $id): void
    {
        $systemCrontabService = Container::make(SysCrontabService::class);
        $data                 = $systemCrontabService->getTask($id);
        if (empty($data)) {
            return;
        }
        if (intval($data['enabled']) === 0) {
            return;
        }
        $crontab = new Crontab($data['rule'], callback: function () use (&$data) { //这里传参必须传引用
            //首次不执行
            if (!$data['first_start']) {
                $data['first_start'] = true;
                return false;
            }
            //运行次数加1,很重要,多进程情况下用来检测当前次数是否已执行
            $data['running_times'] += 1;
            $uuid                  = $this->createTaskUuid($data);
            //获取锁.
            $lock = Locker::cash($uuid);
            // 获取锁失败
            if (!$lock) {
                return;
            }
            try {
                $systemCrontabService = Container::make(SysCrontabService::class);
                $reData               = $systemCrontabService->runOneTask($data['id']);
                $this->writeln($data['title'] . ' 任务#' . $data['id'] . ' ' . $data['rule'] . ' ' . $data['target'], $reData['code']);
                $this->isSingleton($data);
            } finally {
                // 释放锁
                $lock->release();
            }
        });

        $this->crontabPool[$data['id']] = [
            'id'          => $data['id'],
            'create_time' => date('Y-m-d H:i:s'),
            'crontab'     => $crontab,
        ];
    }

    /**
     * 是否单次
     *
     * @param $crontab
     *
     * @return void
     */
    private function isSingleton($crontab): void
    {
        if ($crontab['singleton'] == 0 && isset($this->crontabPool[$crontab['id']])) {
            $this->writeln("定时器销毁", true);
            $this->crontabPool[$crontab['id']]['crontab']->destroy();
            unset($this->crontabPool[$crontab['id']]);
        }
    }

    /**
     * @param $crontab
     *
     * @return string
     * 创建任务uuid
     */
    private function createTaskUuid($crontab): string
    {
        // 以任务id + 运行次数作为唯一id, 这样在过期范围内,只会有一个进程能执行到该次任务
        return 'crontab_' . $crontab['id'] . '_' . $crontab['running_times'];
    }

    /**
     * 输出日志
     *
     * @param      $msg
     * @param bool $isSuccess
     */
    private function writeln($msg, bool $isSuccess = false): void
    {
        if ($this->debug) {
            $workerId = isset($this->worker) && !empty($this->worker) ? $this->worker->id : 0;
            echo 'webman-scheduler:' . $workerId . ' [' . date('Y-m-d H:i:s') . '] ' . $msg . ($isSuccess == 0 ? " [Ok] " : " [Fail] ") . PHP_EOL;
        }
    }
}
