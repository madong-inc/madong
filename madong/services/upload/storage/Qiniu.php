<?php

namespace madong\services\upload\storage;

use madong\exception\UploadException;
use madong\services\upload\BaseUpload;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qiniu extends BaseUpload
{
    protected ?UploadManager $instance = null;
    protected ?string $uploadToken = null;

    public function getInstance(): UploadManager
    {
        return $this->instance ??= new UploadManager();
    }

    public function getUploadToken(): string
    {
        return $this->uploadToken ??= (new Auth($this->config['accessKey'], $this->config['secretKey']))->uploadToken($this->config['bucket']);
    }

    public function uploadFile(array $options = []): array
    {
        $result  = [];
        $dirname = $this->config['dirname'] ?? '';
        $domain  = rtrim($this->config['domain'], '/');

        foreach ($this->files as $key => $file) {
            $uniqueId = $this->getUniqueId($file->getPathname());
            $saveName = $uniqueId . '.' . $file->getUploadExtension();
            $object   = !empty($dirname) ? $dirname . $this->dirSeparator . $saveName : $saveName;

            $temp = [
                'key'         => $key,
                'origin_name' => $file->getUploadName(),
                'save_name'   => $saveName,
                'save_path'   => $object,
                'url'         => $domain . $this->dirSeparator . $object,
                'unique_id'   => $uniqueId,
                'size'        => $file->getSize(),
                'mime_type'   => $file->getUploadMimeType(),
                'extension'   => $file->getUploadExtension(),
                'base_path'   =>$this->dirSeparator . $object
            ];

            try {
                [$ret, $err] = $this->getInstance()->putFile($this->getUploadToken(), $object, $file->getPathname());
                if ($err) {
                    throw new UploadException((string)$err);
                }
                $result[] = $temp;
            } catch (\Throwable $exception) {
                throw new UploadException($exception->getMessage());
            }
        }

        return $result;
    }

    public function uploadServerFile(string $filePath): array
    {
        $file = new \SplFileInfo($filePath);
        if (!$file->isFile()) {
            throw new UploadException('请检查上传文件是否是一个有效的文件，文件不存在: ' . $filePath);
        }

        $uniqueId = hash_file('sha256', $file->getPathname());
        $dirname  = $this->config['dirname'] ?? '';
        $object   = !empty($dirname) ? $dirname . $this->dirSeparator . $uniqueId . '.' . $file->getExtension() : $uniqueId . '.' . $file->getExtension();

        $result = [
            'origin_name' => $file->getRealPath(),
            'save_path'   => $object,
            'url'         => $this->config['domain'] . $this->dirSeparator . $object,
            'unique_id'   => $uniqueId,
            'size'        => $file->getSize(),
            'extension'   => $file->getExtension(),
        ];

        [$ret, $err] = $this->getInstance()->putFile($this->getUploadToken(), $object, $file->getPathname());
        if ($err) {
            throw new UploadException((string)$err);
        }

        return $result;
    }

    public function uploadBase64(string $base64, string $extension = 'png'): array
    {
        $base64   = explode(',', $base64);
        $uniqueId = date('YmdHis') . uniqid();
        $object   = !empty($this->config['dirname']) ? $this->config['dirname'] . $this->dirSeparator . $uniqueId . '.' . $extension : $uniqueId . '.' . $extension;

        [$ret, $err] = $this->getInstance()->put($this->getUploadToken(), $object, base64_decode($base64[1]));
        if ($err) {
            throw new UploadException((string)$err);
        }

        $imgLen   = strlen($base64[1]);
        $fileSize = $imgLen - ($imgLen / 8) * 2;

        return [
            'save_path' => $object,
            'url'       => $this->config['domain'] . $this->dirSeparator . $object,
            'unique_id' => $uniqueId,
            'size'      => $fileSize,
            'extension' => $extension,
        ];
    }
}
