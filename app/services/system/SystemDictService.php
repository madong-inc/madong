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

use app\dao\system\SystemDictDao;
use app\model\system\SystemDictItem;
use madong\basic\BaseService;
use ReflectionClass;
use ReflectionException;
use support\Container;

/**
 * 数据字段服务
 *
 * @author Mr.April
 * @since  1.0
 * @method update($where, $data)
 */
class SystemDictService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemDictDao::class);
    }

    /**
     * 字典项获取
     *
     * @param string $code
     *
     * @return array
     */
    public function findItemsByCode(string $code): array
    {
        $map1   = [
            'code'    => $code,
            'enabled' => 1,
        ];
        $result = $this->get($map1, ['*'], ['items']);
        if (empty($result)) {
            return [];
        }
        $items = $result->getData('items');
        if (empty($items)) {
            return [];
        }
        foreach ($items as $item) {
            //转字符串
            if ($result->getData('data_type') == 1) {
                $item->set('value', (string)$item->getData('value'));
            }
            //整型
            if ($result->getData('data_type') == 2) {
                $item->set('value', (int)$item->getData('value'));
            }
        }

        return $items->visible(['label', 'value', 'ext'])->toArray();
    }

    /**
     * 扫描枚举目录
     *
     * @param array $directories
     *
     * @return array
     */
    public function scanEnums(array $directories): array
    {
        $enumList = [];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*.php');

            foreach ($files as $file) {
                $className = 'app\\num\\' . pathinfo($file, PATHINFO_FILENAME);
                if ($this->isEnumClass($className)) {
                    $enumInfo = $this->getEnumInfo($className);
                    if ($enumInfo) {
                        $enumList[] = $enumInfo;
                    }
                }
            }
        }
        return $enumList;
    }

    /**
     * 是否枚举类
     *
     * @param $className
     *
     * @return bool
     */
    private function isEnumClass($className): bool
    {
        try {
            $reflectionClass = new ReflectionClass($className);
            return $reflectionClass->isEnum();
        } catch (ReflectionException $e) {
            var_dump($e->getMessage());
            return false; // 如果遇到错误则返回 false
        }
    }

    /**
     * 枚举数据
     *
     * @param $className
     *
     * @return array
     */
    private function getEnumInfo($className): array
    {
        $reflectionClass = new ReflectionClass($className);
        $enumInfo        = [
            'name'     => method_exists($className, 'getName') ? $className::getName() : $reflectionClass->getName(), // 枚举类的名称
            'dict_key' => $className,
            'items'    => [],
        ];
        foreach ($reflectionClass->getConstants() as $constantName => $constantValue) {
            $enumInstance        = $reflectionClass->getConstant($constantName);
            $enumInfo['items'][] = [
                'dict_item_key'   => $constantName, // 枚举成员的字典键名
                'dict_item_value' => method_exists($className, 'getDescription') ? $enumInstance->getDescription() : $constantValue, // 示例值
            ];
        }
        return $enumInfo; // 返回构建的枚举类信息
    }

    /**
     * updated
     *
     * @param $id
     * @param $data
     *
     * @return bool
     */
//    public function updated($id, $data): bool
//    {
//        $this->update($id, $data);
//        $systemDictItemService = Container::make(SystemDictItemService::class);
//        $systemDictItemService->update(['dict_id' => $id], ['code' => $data['code']]);
//        return true;
//    }
//
//    /**
//     * 数据删除
//     *
//     * @param string|array $ids
//     * @param bool         $force
//     *
//     * @return mixed
//     */
//    public function destroy(string|array $ids, bool $force = false): mixed
//    {
//        $result = $this->dao->destroy($ids, $force);
//        if ($force) {
//            $systemDictItemService = Container::make(SystemDictItemService::class);
//            $systemDictItemService->delete([['dict_id', 'in', $ids]]);
//        }
//        return $result ?? [];
//    }
}
