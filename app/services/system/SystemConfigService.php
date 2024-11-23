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

namespace app\services\system;

use app\dao\system\SystemConfigDao;
use madong\basic\BaseService;
use madong\exception\AdminException;
use support\Container;

class SystemConfigService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemConfigDao::class);
    }

    /**
     * 获取配置
     *
     * @param string|int $code
     * @param string|int $groupCode
     *
     * @return mixed
     */
    public function getConfig(string|int $code, string|int $groupCode = ''): mixed
    {
        $map = ['code' => $code, 'group_code' => $groupCode];
        return $this->dao->value($map, 'content');
    }

    /**
     * 获取配置分组详情
     *
     * @param string $groupCode
     *
     * @return mixed
     */
    public function getConfigContentValue(string $groupCode): mixed
    {
        $map1 = ['group_code' => $groupCode, 'enabled' => 1];
        $data = $this->dao->getColumn($map1, 'content', 'code');
        if (empty($data)) {
            $data = $this->transformToKeyValue($this->template($groupCode));
        }
        return $data;
    }

    /**
     * 批量添加配置
     *
     * @param array $data
     */
    public function batchUpdateConfig(array $data = []): void
    {
        try {
            $this->transaction(function () use ($data) {
                foreach ($data as $item) {
                    $configModel = $this->dao->get([
                        'code'       => $item['code'],
                        'group_code' => $item['group_code'],
                    ]);
                    if (!empty($configModel)) {
                        $configModel->set('content', $item['content']);
                        $configModel->save();
                    } else {
                        $this->dao->save($item);
                    }
                }
            });
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 返回[key => value]
     *
     * @param array $data
     *
     * @return array
     */
    private function transformToKeyValue(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            if (isset($item['code']) && isset($item['content'])) {
                $result[$item['code']] = $item['content'];
            }
        }
        return $result;
    }

    /**
     * 返回对应的模板
     *
     * @param string $name
     *
     * @return array|array[]
     */
    private function template(string $name): array
    {
        $templates = [
            'local' => [
                ['group_code' => 'local', 'code' => 'root', 'name' => '存储目录', 'content' => 'public', 'is_sys' => 1],
                ['group_code' => 'local', 'code' => 'dirname', 'name' => '目录名称', 'content' => 'upload', 'is_sys' => 1],
                ['group_code' => 'local', 'code' => 'domain', 'name' => '域名地址', 'content' => 'http://127.0.0.1:8899/', 'is_sys' => 1],
            ],
            'oss'   => [
                ['group_code' => 'oss', 'code' => 'accessKeyId', 'name' => 'Access Key ID', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'oss', 'code' => 'accessKeySecret', 'name' => 'Access Key Secret', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'oss', 'code' => 'bucket', 'name' => 'Bucket名称', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'oss', 'code' => 'domain', 'name' => '域名地址', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'oss', 'code' => 'endpoint', 'name' => 'EndPoint', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'oss', 'code' => 'dirname', 'name' => '文件存储目录', 'content' => '', 'is_sys' => 1],
            ],
            'cos'   => [
                ['group_code' => 'cos', 'code' => 'secretId', 'name' => 'Secret ID', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'cos', 'code' => 'secretKey', 'name' => 'Secret Key', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'cos', 'code' => 'bucket', 'name' => 'Bucket名称', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'cos', 'code' => 'domain', 'name' => '域名地址', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'cos', 'code' => 'region', 'name' => 'Region', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'cos', 'code' => 'dirname', 'name' => '文件存储目录', 'content' => '', 'is_sys' => 1],
            ],
            'qiniu' => [
                ['group_code' => 'qiniu', 'code' => 'accessKey', 'name' => 'Access Key', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'qiniu', 'code' => 'secretKey', 'name' => 'Secret Key', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'qiniu', 'code' => 'bucket', 'name' => 'Bucket名称', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'qiniu', 'code' => 'domain', 'name' => '域名地址', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'qiniu', 'code' => 'region', 'name' => 'Region', 'content' => '', 'is_sys' => 1],
                ['group_code' => 'qiniu', 'code' => 'dirname', 'name' => '文件存储目录', 'content' => '', 'is_sys' => 1],
            ],
            's3'    => [
                ['group_code' => 's3', 'code' => 'key', 'name' => 'Key', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'secret', 'name' => 'Secret', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'bucket', 'name' => 'Bucket名称', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'dirname', 'name' => '目录名称', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'domain', 'name' => '域名地址', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'region', 'name' => 'Region', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'version', 'name' => 'Version', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'endpoint', 'name' => 'Endpoint', 'content' => '', 'is_sys' => 1],
                ['group_code' => 's3', 'code' => 'acl', 'name' => 'ACL', 'content' => '', 'is_sys' => 1],
            ],
        ];
        return $templates[$name] ?? [];
    }

}
