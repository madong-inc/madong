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

namespace app\admin\controller\system;

use app\admin\controller\Crud;
use app\common\services\system\SysUploadService;
use core\exception\handler\AdminException;
use core\utils\Json;
use support\Container;
use support\Request;
use Webman\RedisQueue\Client;

class SysUploadController extends Crud
{

    public function __construct()
    {
        parent::__construct();
        $this->service = Container::make(SysUploadService::class);
    }

    /**
     * 下载图片到服务器
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     * @throws \Exception
     */
    public function downloadNetworkImage(Request $request): \support\Response
    {
        $url    = $request->input('url', '');
        $result = $this->service->saveNetworkImage($url);
        return Json::success('操作成功', $result);
    }

    /**
     * 根据id下载资源
     *
     * @param \support\Request $request
     *
     * @return \support\Response|\Webman\Http\Response
     */
    public function downloadResourceById(Request $request): \support\Response|\Webman\Http\Response
    {
        try {
            $id   = $request->route->param('id');
            $data = $this->service->get($id);
            if (empty($data)) {
                throw new AdminException('数据未找到', -1);
            }
            return response()->download($data->path, $data->filename);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 根据hash下载资源
     *
     * @param \support\Request $request
     *
     * @return \support\Response|\Webman\Http\Response
     */
    public function downloadResourceByHash(Request $request): \support\Response|\Webman\Http\Response
    {
        try {
            $hash = $request->route->param('hash');
            $data = $this->service->get(['hash' => $hash]);
            if (empty($data)) {
                throw new AdminException('数据未找到', -1);
            }
            return response()->download($data->path, $data->filename);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 上传图片
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     * @throws \Throwable
     */
    public function uploadImage(Request $request): \support\Response
    {
        try {
            $result = $this->service->upload();
            return Json::success('ok', $result->toArray());
        } catch (\Exception $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 上传文件
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function uploadFile(Request $request): \support\Response
    {
        try {
            $result = $this->service->upload('file');
            return Json::success('ok', $result);
        } catch (\Exception $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 下载导出的excel-后期需要优化实现下载成功后删除原文件
     *
     * @param \support\Request $request
     *
     * @return \support\Response|null
     */
    public function downloadExcel(Request $request): ?\support\Response
    {
        try {
            $param        = $request->all();
            $file         = $param['file_path'];//文件路径
            $downloadName = $param['file'] ?? date('Y-m-d His', time());//文件下载名称
            $filePath     = runtime_path() . $file;

            //生成的文件5分钟后超时自动删除
            $queue = 'remove-excel-file';
            Client::send($queue, $param, 300);
            return response()->download($filePath, $downloadName);
        } catch (\Throwable $e) {
            //添加日志
        }
    }




//    public function downloadExcel(Request $request)
//    {
//        try {
//            $param = $request->all();
//            $file  = ltrim($param['file_path'] ?? '', '/');
//
//            // 路径安全校验
//            $basePath = realpath(runtime_path());
//            $realPath = realpath($basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
//            if (!$realPath || strpos($realPath, $basePath) !== 0 || !is_file($realPath)) {
//                throw new \RuntimeException('文件不存在或路径非法');
//            }
//
//            // 统一使用UTF-8编码
//            $downloadName = $param['file'] ?? date('Y-m-d_His');
//            $encodedName  = rawurlencode($downloadName);
//
//            // 创建响应
//            $response = new BinaryFileResponse($realPath);
//            $response->setContentDisposition(
//                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
//                "filename*=UTF-8''{$encodedName}.xlsx"
//            );
//
//            $response->headers->add([
//                'Cache-Control' => 'private, max-age=600',
//                'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
//            ]);
//
//            $response->headers->set('Content-Length', filesize($realPath));
//
//            $response->deleteFileAfterSend(true);
//
//            return $response;
//        } catch (\Throwable $e) {
//            return response()->json([
//                'code' => 500,
//                'msg'  => '下载失败：' . $e->getMessage(),
//            ], 500);
//        }
//    }

}
