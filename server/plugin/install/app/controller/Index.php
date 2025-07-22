<?php

namespace plugin\install\app\controller;

use core\db\DataImporterService;
use core\utils\Util;
use plugin\cmdr\app\service\Terminal;
use plugin\install\app\common\Json;
use Webman\RedisQueue\Client;

/**
 * 安装
 *
 * @author Mr.April
 * @since  1.0
 */
class Index
{

    /**
     * 安装首页
     */
    public function index(): \support\Response
    {
        clearstatcache();

        if (is_file(base_path() . '/install.lock')) {
            return view('index/error', [
                'msg'       => '管理后台已安装！如需重新安装，请删除文件 install.lock 并重启',
                'url'       => '/admin',
                'copyright' => '© 2025 madong.tech 版权所有',
            ]);
        }
        return view('index/index');
    }

    /**
     * 环境检测
     */
    public function step2(): \support\Response
    {
        return view('index/step2', [
            'isOK'          => true, // 初始通过
            'iswrite_array' => ['/.env'], // 检测是否可写的路径
            'exists_array'  => ['curl_init', 'bcadd', 'mb_substr', 'simplexml_load_string'], // 获取检测的函数数据
            'extendArray'   => getRequiredExtensions(), // 获取扩展
            'copyright'     => '© 2025 madong.tech 版权所有',
        ]);
    }

    /**
     * 设定配置
     */
    public function step3(): \support\Response
    {
        $isOK = request()->post('isOK', false);
        if (!$isOK) redirect("/app/install/index/step2");
        return view('index/step3', [
            'currentHost' => (request()->getRemotePort() == 443 ? 'https://' : 'http://') . request()->header('host') . '/',
            'copyright'   => '© 2025 madong.tech 版权所有',
        ]);
    }

    /**
     * 执行安装
     */
    public function step4(): \support\Response
    {
        try {
            //数据库配置
            $dbHost     = request()->get('db_host', '');
            $dbName     = request()->get('db_name', '');
            $dbPrefix   = request()->get('db_pre', 'ma_');
            $dbUser     = request()->get('db_user', '');
            $dbPassword = request()->get('db_pwd', '');
            $dbPort     = request()->get('db_port', '3306');
            $overwrite  = request()->get('overwrite', 'off');
            $overwrite  = $overwrite == 'on';

            //redis 配置
            $redisHost = request()->get('redis_host', '127.0.0.1');
            $redisPort = request()->get('redis_port', '6379');
            $redisPwd  = request()->get('redis_pwd', null);

            //用户信息配置
            $company  = request()->get('company_name', 'xxxx 有限公司');
            $platform = request()->get('platform', 'admin');
            $username = request()->get('username', 'admin');
            $password = request()->get('password', '123456');

            //验证是否安装
            $is_install_file = base_path() . '/install.lock';
            // 1.0检查是否已安装
            if (is_file($is_install_file)) {
                return Json::fail('管理后台已经安装！如需重新安装，请删除/install.lock文件并重启: ');
            }
            //发布前端
            $result = $this->runBuild();
            //移动文件
            Terminal::mvDist();

            $dataImporterService = new DataImporterService();
            $pdo                 = $dataImporterService->getPdo($dbHost, $dbUser, $dbPassword, $dbPort);
            // 针对 MySQL 8+ 的严格模式进行设定，放宽SQL模式以兼容旧版行为
//            $pdo->exec("SET sql_mode = 'ONLY_FULL_GROUP_BY,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            // 关闭自动提交 使用手动事务提交
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
            $tablesToInstall = [
                //平台管理
                'ma_mt_db_setting',
                'ma_mt_tenant',
                'ma_mt_tenant_package',
                'ma_mt_tenant_session',
                'ma_mt_tenant_subscription',
                'ma_mt_tenant_subscription_casbin',
                //系统设置
                'ma_sys_admin',
                'ma_sys_admin_casbin',
                'ma_sys_admin_dept',
                'ma_sys_admin_post',
                'ma_sys_admin_role',
                'ma_sys_admin_tenant',
                'ma_sys_config',
                'ma_sys_crontab',
                'ma_sys_crontab_log',
                'ma_sys_dept',
                'ma_sys_dept_leader',
                'ma_sys_dict',
                'ma_sys_dict_item',
                'ma_sys_login_log',
                'ma_sys_menu',
                'ma_sys_message',
                'ma_sys_notice',
                'ma_sys_operate_log',
                'ma_sys_post',
                'ma_sys_rate_limiter',
                'ma_sys_rate_restrictions',
                'ma_sys_recycle_bin',
                'ma_sys_role',
                'ma_sys_role_casbin',
                'ma_sys_role_dept',
                'ma_sys_role_menu',
                'ma_sys_role_scope_dept',
                'ma_sys_route',
                'ma_sys_route_cate',
                'ma_sys_upload',
                'ma_cache',
                //策略表
                'ma_casbin_rule',
            ];
            $pdo->beginTransaction();
            //1.0 安装数据表
            $dataImporterService->installDatabaseTables($pdo, $tablesToInstall, $dbName, $overwrite);
            //2.0 导入数据表数据
            //2.1 导入菜单
            $menus = include base_path() . '/scripts/config/menu.php';
            $field = ['pid', 'title', 'app', 'code', 'icon', 'sort', 'type', 'is_show', 'is_link', 'is_cache', 'path', 'component', 'redirect', 'enabled'];
            $dataImporterService->importTreeData($pdo, 'ma_sys_menu', $field, $menus);
            //2.2 导入字典
            $dict   = include base_path() . '/scripts/config/dict.php';
            $field1 = ['group_code', 'name', 'code', 'sort', 'data_type', 'description', 'enabled', 'created_by', 'updated_by'];
            $field2 = ['label', 'value', 'code', 'sort', 'enabled', 'created_by', 'updated_by', 'remark'];
            $dataImporterService->importWithRelated($pdo, 'ma_sys_dict', $field1, 'ma_sys_dict_item', $field2, $dict, ['pidKey' => 'dict_id']);
            //2.3 导入默认数据库配置
            $dbInfo = [
                [
                    "id"          => 1,
                    "name"        => $company,
                    "description" => "默认数据库",
                    "driver"      => "mysql",
                    "host"        => $dbHost,
                    "port"        => $dbPort,
                    "database"    => $dbName,
                    "username"    => $dbUser,
                    "password"    => $dbPassword,
                    "prefix"      => $dbPrefix,
                    "is_default"  => 1,
                    "enabled"     => 1,
                    "created_at"  => time(),
                    "created_by"  => 1,
                ],
            ];
            $field  = ["id", "name", "description", "driver", "host", "port", "database", "username", "password", "prefix", "variable", "is_default", "enabled", "created_at", "created_by", "updated_at", "updated_by", "deleted_at"];
            $dataImporterService->importData($pdo, 'ma_mt_db_setting', $field, $dbInfo);

            //2.4导入租户信息
            $tenantInfo = [
                [
                    "id"             => 1,
                    "db_name"        => $dbName,
                    "code"           => "platform",
                    "type"           => 0,
                    "contact_person" => $username,
                    "contact_phone"  => "18888888888",
                    "company_name"   => $company,
                    "license_number" => "",
                    "address"        => "中国",
                    "description"    => "内置账号",
                    "domain"         => "https://www.madong.tech",
                    "enabled"        => 1,
                    "is_default"     => 1,
                    "created_by"     => 1,
                    "created_at"     => time(),
                ],
            ];
            $field      = ["id", "db_name", "code", "type", "contact_person", "contact_phone", "company_name", "license_number", "address", "description", "domain", "enabled", "is_default", "expired_at", "deleted_at", "created_by", "created_at", "updated_by", "updated_at"];
            $dataImporterService->importData($pdo, 'ma_mt_tenant', $field, $tenantInfo);

            //2.5 导入系统配置数据
            $configuration = include base_path() . '/scripts/config/configuration.php';
            $field         = ["id", "tenant_id", "group_code", "code", "name", "content", "is_sys", "enabled", "created_at", "created_by", "updated_at", "updated_by", "deleted_at", "remark"];
            $dataImporterService->importData($pdo, 'ma_sys_config', $field, $configuration);
            //2.6 导入管理员数据
            $data  = [[
                          'id'              => (int)1,
                          'user_name'       => $username,
                          'password'        => password_hash($password, PASSWORD_DEFAULT),
                          'real_name'       => '超级管理员',
                          'nick_name'       => '超级管理员',
                          'is_super'        => 1,//超级管理员标识
                          'is_tenant_admin' => 1,//租户管理员标识
                          'created_at'      => time(),
                          'updated_at'      => time(),
                      ]];
            $field = ['id', 'user_name', 'real_name', 'nick_name', 'password', 'is_super','is_tenant_admin', 'mobile_phone', 'email', 'avatar', 'signed', 'dashboard', 'dept_id', 'enabled', 'login_ip', 'login_time', 'backend_setting', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at', 'sex', 'remark', 'birthday', 'tel', 'is_locked'];
            $dataImporterService->importData($pdo, 'ma_sys_admin', $field, $data);
            //2.7 导入管理员关联租户数据
            $data  = [[
                          'admin_id'   => 1,
                          'tenant_id'  => 1,
                          'is_super'   => 1,
                          'is_default' => 1,
                          'priority'   => -1,
                          'create_at'  => time(),
                          'update_at'  => time(),
                      ]];
            $field = ['id', 'admin_id', 'tenant_id', 'is_super', 'is_default', 'priority', 'created_at', 'updated_at'];
            $dataImporterService->importData($pdo, 'ma_sys_admin_tenant', $field, $data);
            // 3.0 获取env模板内容
            $envStr = generateEnvTemplate();
            $envStr = str_replace('~db_host~', $dbHost, $envStr);
            $envStr = str_replace('~db_name~', $dbName, $envStr);
            $envStr = str_replace('~db_user~', $dbUser, $envStr);
            $envStr = str_replace('~db_pwd~', $dbPassword, $envStr);
            $envStr = str_replace('~db_port~', $dbPort, $envStr);
            $envStr = str_replace('~db_prefix~', $dbPrefix, $envStr);
            $envStr = str_replace('~redis_host~', $redisHost, $envStr);
            $envStr = str_replace('~redis_port~', $redisPort, $envStr);
            $envStr = str_replace('~redis_pwd~', $redisPwd, $envStr);

            // 4.0 写入.env配置文件
            $fp = fopen(base_path() . '/.env', 'w');
            fwrite($fp, $envStr);
            fclose($fp);

            // 5.0 写入安装锁定文件
            $fp = fopen(base_path() . '/install.lock', 'w');
            fwrite($fp, '程序已正确安装，重新安装请删除本文件');
            fclose($fp);

            // 6.0 提交pdo 数据
            $pdo->commit();
            return Json::success('安装成功', $result);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 安装完成
     *
     * @return \support\Response
     */
    public function step5(): \support\Response
    {
        $isOK = request()->input('isOK', false);
        //验证是否安装
        $is_install_file = base_path() . '/install.lock';
        //检查是否已安装
        if (is_file($is_install_file)) {
            $isOK = true;
        }
        return view('index/step5', [
            'home'      => '/',
            'isOK'      => $isOK,
            'copyright' => '© 2025 madong.tech 版权所有',
        ]);
    }

    /**
     * 数据库连接检测
     */
    public function check(): string
    {
        $host = request()->get('db_host', '');
        $port = request()->get('db_port', '');
        $user = request()->get('db_user', '');
        $pwd  = request()->get('db_pwd', '');
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8";
            new \PDO($dsn, $user, $pwd);
            return 'true';
        } catch (\Exception $e) {
            return 'false';
        }
    }

    /**
     * 前端发布
     *
     * @return array
     */
    public function runBuild(): array
    {
        // 获取web目录路径（base_path的上一级目录中的web子目录）
        $webPath = dirname(base_path()) . '/web';
        // 验证目录是否存在
        if (!is_dir($webPath)) {
            throw new \RuntimeException("前端目录不存在: {$webPath}");
        }

        if (!Terminal::checkPnpm()) {
            throw new \RuntimeException('PNPM未安装或不可用');
        }

        // 执行命令
        return [
            'install' => Terminal::execute(
                'pnpm install',
                $webPath
            ),
            'build'   => Terminal::execute(
                'pnpm run build',
                $webPath
            ),
        ];
    }
}
