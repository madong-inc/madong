<?php

namespace madong\services\upload\storage;

use madong\exception\UploadException;
use madong\services\upload\BaseUpload;

/**
 * 本地上传
 *
 * @author Mr.April
 * @since  1.0
 */
class Local extends BaseUpload
{
    public function uploadFile(array $options = []): array
    {
        $result   = [];
        $root     = $this->getRootPath();
        $dirname  = $this->config['dirname'] ?? '';
        $basePath = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!$this->createDir($basePath)) {
            throw new UploadException('文件夹创建失败，请核查是否有对应权限。');
        }
        $domain = rtrim($this->config['domain'] ?? '', '/');
        foreach ($this->files as $key => $file) {
            $uniqueId     = $this->getUniqueId($file->getPathname());
            $saveFilename = $uniqueId . '.' . $file->getUploadExtension();
            $savePath     = $basePath . $saveFilename;
            $url          = $domain . $this->dirSeparator . $saveFilename;
            if (!empty($dirname)) {
                $savePath = $root . $this->dirSeparator . $dirname . $this->dirSeparator . $saveFilename;
                $url      = $domain . $this->dirSeparator . $dirname . $this->dirSeparator . $saveFilename;
            }
            $temp = [
                'key'         => $key,
                'origin_name' => $file->getUploadName(),
                'save_name'   => $saveFilename,
                'save_path'   => $savePath,
                'url'         => $url,
                'unique_id'   => $uniqueId,
                'size'        => $file->getSize(),
                'mime_type'   => $file->getUploadMimeType(),
                'extension'   => $file->getUploadExtension(),
                'base_path'   => empty($dirname) ? $this->dirSeparator . $saveFilename : $this->dirSeparator . $dirname . $this->dirSeparator . $saveFilename
            ];
            $file->move($savePath);
            $result[] = $temp;
        }
        return $result;
    }

    protected function createDir(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }
        $parent = dirname($path);
        if (!is_dir($parent) && !$this->createDir($parent)) {
            return false;
        }
        return mkdir($path, 0755, true);
    }

    private function getRootPath(): string
    {
        $root = $this->config['root'] ?? '';
        return match ($root) {
            'public' => public_path(),
            'runtime' => runtime_path(),
            'default' => runtime_path(),
        };
    }

    function uploadServerFile(string $filePath): mixed
    {
        // TODO: Implement uploadServerFile() method.
    }

    public function uploadBase64(string $base64, string $extension = 'JPEG'): mixed
    {
        // TODO: Implement uploadBase64() method.
    }
}
