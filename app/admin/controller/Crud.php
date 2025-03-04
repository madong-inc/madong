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

namespace app\admin\controller;

use support\Request;
use madong\utils\JwtAuth;
use madong\exception\AdminException;
use madong\utils\Json;
use madong\utils\Tree;
use app\services\system\SystemUserService;
use think\Model;

/**
 * @author Mr.April
 * @since  1.0
 */
class Crud extends Base
{

    /**
     * 列表
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(Request $request): \support\Response
    {
        try {
            [$where, $format, $limit, $field, $order, $page] = $this->selectInput($request);
            $methods = [
                'select'     => 'formatSelect',
                'tree'       => 'formatTree',
                'table_tree' => 'formatTableTree',
                'normal'     => 'formatNormal',
            ];

            $format_function = $methods[$format] ?? 'formatNormal';
            $total           = $this->service->getCount($where);
            $list            = $this->service->selectList($where, $field, $page, $limit, $order, [], false);
            return call_user_func([$this, $format_function], $list, $total);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function select(Request $request): \support\Response
    {
        try {
            [$where, $format, $limit, $field, $order, $page] = $this->selectInput($request);
            $data = $this->service->selectList($where, $field, 0, 99999, $order, [], true);
            return Json::success('ok', $data);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }

    }

    /**
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function create(Request $request): \support\Response
    {
        try {
            $id = $request->get('id');
            throw new AdminException('表单不存在');
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 插入数据
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function store(Request $request): \support\Response
    {
        try {
            $data = $this->insertInput($request);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('store')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $model = $this->service->save($data);
            if (empty($model)) {
                throw new AdminException('插入失败');
            }
            $pk = $model->getPk();
            return Json::success('ok', [$pk => $model->getData($pk)]);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 查询详情
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function show(Request $request): \support\Response
    {
        try {
            $id   = $request->route->param('id');
            $data = $this->service->get($id);
            if (empty($data)) {
                throw new AdminException('数据未找到', 400);
            }
            return Json::success('ok', $data->toArray());
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage(), [], $e->getCode());
        }
    }

    public function edit(Request $request): \support\Response
    {
        try {
            $id = $request->route->param('id');
            throw new AdminException('表单不存在');
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 更新
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function update(Request $request): \support\Response
    {
        try {
            $id   = $request->route->param('id');
            $data = $this->insertInput($request);
            if (isset($this->validate) && $this->validate) {
                if (!$this->validate->scene('update')->check($data)) {
                    throw new \Exception($this->validate->getError());
                }
            }
            $this->service->update($id, $data);
            return Json::success('ok', []);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 删除
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function destroy(Request $request): \support\Response
    {
        try {
            $id = $request->route->param('id'); // 获取路由地址 id从

            $data = $request->input('data', []);
            $data = !empty($id) && $id !== '0' ? $id : $data;
            if (empty($data)) {
                throw new AdminException('参数错误');
            }
            $result = $this->service->transaction(function () use ($data) {
                $data       = is_array($data) ? $data : explode(',', $data);
                $deletedIds = [];
                foreach ($data as $id) {
                    $item = $this->service->get($id);
                    if (!$item) {
                        continue; // 如果找不到项，跳过
                    }
                    $item->delete();
                    $primaryKey   = $item->getPk();
                    $deletedIds[] = $item->{$primaryKey};
                }
                return $deletedIds;
            });
            return Json::success('ok', $result);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 数据恢复
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function recovery(Request $request): \support\Response
    {
        try {
            $id   = $request->route->param('id');
            $data = $request->input('data', []);
            $data = !empty($id) && $id !== '0' ? $id : $data;
            if (empty($data)) {
                throw new AdminException('参数错误');
            }
            $this->service->transaction(function () use ($data) {
                $data = is_array($data) ? $data : explode(',', $data);
                foreach ($data as $id) {
                    $this->service->update($id, ['delete' => null]);
                }
            });
            return Json::success('操作成功', []);
        } catch (\Throwable $e) {
            return Json::fail($e->getMessage());
        }
    }

    protected function selectInput(Request $request): array
    {
        $field        = $request->input('field', '*');
        $sort         = $request->input('order', 'sort');
        $format       = $request->input('format', 'normal');
        $limit        = (int)$request->input('limit', $format === 'tree' ? 10000 : 10);
        $limit        = $limit <= 0 ? 10 : $limit;
        $param        = $request->all();
        $page         = (int)$request->input('page');
        $page         = $page > 0 ? $page : 1;
        $model        = $this->service->getModel();
        $allow_column = $model->getFields();
        $parts        = explode(' ', $sort);
        $order        = '';
        if (in_array($parts[0], $allow_column)) {
            $rank  = $parts[1] ?? 'asc';
            $order = $parts[0] . ' ' . $rank;
        }

        if (!in_array($field, $allow_column)) {
            $field = '*';
        }

        $where = [];
        foreach ($param as $column => $value) {
            $prefix       = '';
            $actualColumn = $column;
            if (preg_match('/^([A-Z_]+)_(.*)$/', $column, $matches)) {
                $prefix       = $matches[1]; // 前缀，包括下划线
                $actualColumn = strtolower($matches[2]); // 实际字段名并转为小写
            }

            // 检查值是否为 null，或者不是允许的列，或者是数组且不符合条件
            if ($value === null || !in_array($actualColumn, $allow_column) || (is_array($value) && (!isset($value[0]) || !in_array($value[0], ['null', 'not null']) && !isset($value[1])))) {
                continue; // 跳过不符合条件的字段
            }

            // 根据前缀构建查询条件
            switch ($prefix) {
                case 'IN':
                    $where[] = [$actualColumn, 'IN', $value]; // 处理 IN 条件
                    break;
                case 'LIKE':
                    $where[] = [$actualColumn, 'LIKE', '%' . $value . '%']; // 处理 LIKE 条件
                    break;
                case 'GT':
                    $where[] = [$actualColumn, '>', $value]; // 处理大于条件
                    break;
                case 'LT':
                    $where[] = [$actualColumn, '<', $value]; // 处理小于条件
                    break;
                case 'GTE':
                    $where[] = [$actualColumn, '>=', $value]; // 处理大于等于条件
                    break;
                case 'LTE':
                    $where[] = [$actualColumn, '<=', $value]; // 处理小于等于条件
                    break;
                case 'NE':
                    $where[] = [$actualColumn, '!=', $value]; // 处理不等于条件
                    break;
                case 'BETWEEN':
                    // 处理 BETWEEN 条件，假设值为数组
                    if (is_array($value) && count($value) === 2) {
                        $where[] = [$actualColumn, 'BETWEEN', $value]; // 处理 BETWEEN 条件
                    }
                    break;
                default:
                    $where[] = [$actualColumn, '=', $value]; // 默认处理
                    break;
            }
        }
        return [$where, $format, $limit, $field, $order, $page];
    }

    /**
     * 插入前置方法
     *
     * @param Request $request
     *
     * @return array
     */
    protected function insertInput(Request $request): array
    {
        $data           = $this->inputFilter($request->all());
        $password_filed = 'password';
        if (isset($data[$password_filed])) {
            $data[$password_filed] = JwtAuth::passwordHash($data[$password_filed]);
        }
        return $data;
    }

    /**
     * 对用户输入表单过滤
     *
     * @param array $data
     * @param array $skipKeys
     *
     * @return array
     */
    protected function inputFilter(array $data, array $skipKeys = []): array
    {
        $model   = $this->service->getModel();
        $columns = $model->getFields();

        foreach ($data as $col => $item) {
            // 检查是否在跳过的键中，或者不在列中
            if (!in_array($col, $columns) && !in_array($col, $skipKeys)) {
                unset($data[$col]);
                continue;
            }
        }

        // 处理时间字段
        if (empty($data['created_time']) && !in_array('created_time', $skipKeys)) {
            unset($data['created_time']);
        }
        if (empty($data['updated_time']) && !in_array('updated_time', $skipKeys)) {
            unset($data['updated_time']);
        }

        return $data;
    }

    /**
     * 格式化树
     *
     * @param $items
     *
     * @return \support\Response
     */
    protected function formatTree($items): \support\Response
    {
        $format_items = [];
        foreach ($items as $item) {
            $format_items[] = [
                'name'  => $item->title ?? $item->name ?? $item->id,
                'value' => (string)$item->id,
                'id'    => $item->id,
                'pid'   => $item->pid,
            ];
        }
        $tree = new Tree($format_items);
        return Json::success('ok', $tree->getTree());
    }

    /**
     * 格式化表格树
     *
     * @param $data
     * @param $total
     *
     * @return \support\Response
     */
    protected function formatTableTree($data, $total): \support\Response
    {
        $tree  = new Tree($data->toArray());
        $items = $tree->getTree();
        return Json::success('ok', $items);
    }

    /**
     * 格式化下拉列表
     *
     * @param $items
     *
     * @return \support\Response
     */
    protected function formatSelect($items): \support\Response
    {
        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'label' => $item->title ?? $item->name ?? $item->real_name ?? $item->id,
                'value' => $item->id,
            ];
        }

        return Json::success('ok', $formatted_items);
    }

    /**
     * 通用格式化
     *
     * @param $items
     * @param $total
     *
     * @return \support\Response
     */
    protected function formatNormal($items, $total): \support\Response
    {
        return Json::success('ok', compact('items', 'total'));
    }

    public function dev(Request $request): \support\Response
    {
        return Json::fail('接口开发中');
    }

}
