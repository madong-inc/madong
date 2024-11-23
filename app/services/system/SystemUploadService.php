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

use app\dao\system\SystemUploadDao;
use madong\basic\BaseService;
use madong\exception\AdminException;
use madong\services\upload\UploadFile;
use madong\utils\Arr;
use support\Container;

class SystemUploadService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemUploadDao::class);
    }

    /**
     * 远程下载图片到本地
     *
     * @param string $url
     *
     * @return mixed
     * @throws \Exception
     */
    public function saveNetworkImage(string $url): array
    {
        $config = UploadFile::getConfig('local');
        $data   = file_get_contents($url);
        if ($data === false) {
            throw new AdminException('获取文件资源失败');
        }
        $image_resource = imagecreatefromstring($data);
        if (!$image_resource) {
            throw new AdminException('创建图片资源失败');
        }
        $filename       = basename($url);
        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
        $full_dir       = runtime_path() . '/resource/';
        if (!is_dir($full_dir)) {
            mkdir($full_dir, 0777, true);
        }
        $save_path    = $full_dir . $filename;
        $content_type = 'image/';
        switch ($file_extension) {
            case 'jpg':
            case 'jpeg':
                $content_type = 'image/jpeg';
                $result       = imagejpeg($image_resource, $save_path);
                break;
            case 'png':
                $content_type = 'image/png';
                $result       = imagepng($image_resource, $save_path);
                break;
            case 'gif':
                $content_type = 'image/gif';
                $result       = imagegif($image_resource, $save_path);
                break;
            default:
                imagedestroy($image_resource);
                throw new AdminException('文件格式错误');
        }
        imagedestroy($image_resource);
        if (!$result) {
            throw new AdminException('文件保存失败');
        }
        $hash = md5_file($save_path);
        $size = filesize($save_path);

        $result = $this->dao->get(['hash' => $hash]);
        if (!empty($result)) {
            unlink($save_path);
            return $result->toArray();
        }

        $systemConfigService = Container::make(SystemConfigService::class);
        $local               = $systemConfigService->dao->get(['code' => 'local']);
        if (empty($local)) {
            throw new AdminException('缺少本地上传配置信息');
        }
        $root     = Arr::fetchConfigValue($config, 'root');
        $folder   = date('Ymd');
        $full_dir = base_path() . DIRECTORY_SEPARATOR . $root . $folder . DIRECTORY_SEPARATOR;
        if (!is_dir($full_dir)) {
            mkdir($full_dir, 0777, true);
        }
        $object_name = bin2hex(pack('Nn', time(), random_int(1, 65535))) . ".$file_extension";
        $newPath     = $full_dir . $object_name;

        copy($save_path, $newPath);
        unlink($save_path);
        $domain  = Arr::fetchConfigValue($config, 'domain');
        $dirname = Arr::fetchConfigValue($config, 'dirname');
        $baseUrl = $domain . $dirname . $folder . '/';

        $info['platform']          = 'local';
        $info['original_filename'] = $filename;
        $info['filename']          = $object_name;
        $info['hash']              = $hash;
        $info['content_type']      = $content_type;
        $info['base_path']         = $root . $folder . '/' . $object_name;
        $info['ext']               = $file_extension;
        $info['size']              = $size;
        $info['size_info']         = formatBytes($size);
        $info['url']               = $baseUrl . $object_name;//访问地
        $result                    = $this->dao->save($info);
        return $result->toArray();
    }

    /**
     * 文件上传
     *
     * @param string $upload
     * @param bool   $isLocal
     *
     * @return mixed
     */
    public function upload(string $upload = '', bool $isLocal = false): mixed
    {
        try {
            return $this->transaction(function () use ($upload, $isLocal) {
                $systemConfigService = Container::make(SystemConfigService::class);
                $baseConfig          = $systemConfigService->getConfigContentValue('basic_upload_setting');//获取上次配置
                if (empty($baseConfig)) {
                    throw new AdminException('缺少上传配置信息');
                }
                $type    = Arr::fetchConfigValue($baseConfig, 'mode') ?: 'local';//上次模式默认本地
                if ($isLocal) {
                    $type = 'local';
                }

                $result = UploadFile::uploadFile();
                $data   = $result[0];
                $hash   = $data['unique_id'];

                // 检查文件是否已存在
                if ($filesInfo = $this->dao->get(['hash' => $hash])) {
                    return $filesInfo;
                }

                $url    = str_replace('\\', '/', $data['url']);
                $path   = str_replace('\\', '/', $data['save_path']);

                $inData = [
                    'platform'          => $type,
                    'original_filename' => $data['origin_name'] ?? '',
                    'filename'          => $data['save_name'],
                    'hash'              => $hash,
                    'content_type'      => $data['mime_type'],
                    'base_path'         => $data['base_path'],
                    'ext'               => $data['extension'],
                    'size'              => $data['size'],
                    'size_info'         => formatBytes($data['size']),
                    'url'               => $url,
                    'path'              => $path
                ];
                return $this->dao->save($inData);
            });
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

}
