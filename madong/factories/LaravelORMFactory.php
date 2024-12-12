<?php

namespace madong\factories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use madong\exception\MadongException;
use support\Container;
use support\Model;

class LaravelORMFactory
{
    private mixed $model;

    public function __construct(string|Model|null $model = null)
    {
        $this->model = $model;
    }
    /**
    * 获取条数
    *
    * @param array $where
    * @param bool  $search
    *
    * @return int
    * @throws \Exception
    */
    public function count(array $where = [], bool $search = false): int
    {
        // 获取查询构建器实例
        $query = $this->getModel()->query();
        if ($search) {
            $query = $this->search($where); // search 返回的是一个查询构建器
        } else {
            $query->where($where); // 应用 where 条件
        }

        // 返回满足条件的记录数量
        return $query->count();
    }


    /**
     * 查询列表
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     * @param string $order
     * @param array  $with
     * @param bool   $search
     *
     * @return \Illuminate\Database\Eloquent\Collection|\madong\factories\Illuminate\Database\Eloquent\Builder|null
     * @throws \Exception
     */
    public function selectList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false)
    {
        // 使用 selectModel 方法获取查询构建器
        $query = $this->selectModel($where, $field, $page, $limit, $order, $with, $search);

        // 如果字段不是 '*'，则应用 selectRaw()
        if ($field !== '*') {
            $query->selectRaw($field); // 确保在查询构建器上调用
        }
        // 应用分页
        if ($page > 0 && $limit > 0) {
            // 只返回数据部分
            return $query->paginate($limit, ['*'], 'page', $page)->getCollection();
        }
        return $query->get(); // 返回所有数据
    }

    /**
     * 获取某些条件数据
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     * @param string $order
     * @param array  $with
     * @param bool   $search
     *
     * @return \Illuminate\Database\Eloquent\Collection|\madong\factories\Illuminate\Database\Eloquent\Builder|null
     * @throws \Exception
     */
    public function selectModel(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false)
    {
        // 获取模型的查询构建器
        $query = $this->getModel()->query();

        // 根据是否需要搜索来决定查询条件
        if ($search) {
            $query = $this->search($where); // search 返回的是一个查询构建器
        } else {
            $query->where($where); // 应用 where 条件
        }
        // 应用字段选择
        if ($field !== '*') {
            $query->selectRaw($field); // 在这里应用 selectRaw
        }
        // 应用分页和其他查询条件
        if ($page > 0 && $limit > 0) {
            $query->paginate($limit, ['*'], 'page', $page);
        }
        if ($order !== '') {
            $query->orderByRaw($order);
        }
        if (!empty($with)) {
            $query->with($with);
        }
        return $query; // 返回查询构建器
    }

    /**
     * 获取条数
     *
     * @param array $where
     *
     * @return int
     */
    public function getCount(array $where): int
    {
        return $this->getModel()->where($where)->count();
    }

    /**
     * 计算符合条件的唯一记录数量
     *
     * @param array  $where
     * @param string $field
     * @param bool   $search
     *
     * @return int
     * @throws \Exception
     */
    public function getDistinctCount(array $where, string $field, bool $search = true): int
    {
        // 构建查询
        $query = $this->getModel();
        // 应用搜索条件
        if ($search) {
            $query = $this->search($query, $where);
        } else {
            $query = $query->where($where);
        }
        // 获取唯一计数
        return $query->distinct()->count($field);
    }

    /**
     * 获取模型
     *
     * @return mixed
     */
    public function getModel(): mixed
    {
        try {
            $className = $this->model;
            if (is_object($className)) {
                return $className;
            }
            if (!class_exists($className)) {
                throw new MadongException($className . ' 不是一个有效的模型类');
            }
            return Container::make($className);
        } catch (\Throwable $e) {
            throw new MadongException($className . ' 未知模型: ' . $e->getMessage());
        }
    }

    /**
     * 获取模型主键
     *
     * @return string
     * @throws \Exception
     */
    public function getPk(): string
    {
        return $this->getModel()->getKeyName();
    }

    /**
     * 获取表名
     *
     * @return string
     * @throws \ReflectionException|\Exception
     */
    public function getTableName(): string
    {
        return $this->getModel()->getTable();
    }

    /**
     * 获取一条数据
     *
     * @param            $id
     * @param array|null $field
     * @param array|null $with
     * @param string     $order
     *
     * @return mixed
     * @throws \Exception
     */
    public function get($id, ?array $field = null, ?array $with = [], string $order = ''): mixed
    {
        // 构建查询条件
        $where = is_array($id) ? $id : [$this->getPk() => $id];
        // 使用 Eloquent 查询构建器
        $query = $this->getModel()->where($where);
        // 添加关联加载
        if (!empty($with)) {
            $query->with($with);
        }
        // 添加排序条件
        if ($order !== '') {
            $query->orderByRaw($order);
        }
        // 返回查询结果
        return $query->select($field ?? ['*'])->first();
    }

    /**
     * 查询一条数据是否存在
     *
     * @param        $map
     * @param string $field
     *
     * @return bool
     * @throws \Exception
     */
    public function be($map, string $field = ''): bool
    {
        // 如果 $map 不是数组且 $field 为空，使用主键
        if (!is_array($map) && empty($field)) {
            $field = $this->getPk();
        }

        // 如果 $map 不是数组，将其转换为数组
        $map = !is_array($map) ? [$field => $map] : $map;

        // 使用 Eloquent 查询构建器检查记录是否存在
        return $this->getModel()->where($map)->exists();
    }

    /**
     * 根据条件获取一条数据
     *
     * @param array       $where
     * @param string|null $field
     * @param array       $with
     *
     * @return Model|null
     * @throws \ReflectionException|\Exception
     */
    public function getOne(array $where, ?string $field = '*', array $with = []): ?Model
    {
        // 将字段字符串转换为数组
        $fieldArray = $field === '*' ? ['*'] : explode(',', $field);
        // 使用 Eloquent 查询构建器获取一条数据
        return $this->getModel()->with($with)->where($where)->select($fieldArray)->first();
    }

    /**
     * 获取某字段的值
     *
     * @param             $where
     * @param string|null $field
     *
     * @return mixed
     * @throws \Exception
     */
    public function value($where, ?string $field = null): mixed
    {
        $pk    = $this->getPk(); // 获取主键
        $query = $this->getModel()->where($this->setWhere($where)); // 设置查询条件

        return $query->value($field ?? $pk); // 返回指定字段的值，默认为主键
    }

    /**
     * 获取某个字段数组
     *
     * @param array  $where
     * @param string $field
     * @param string $key
     *
     * @return array
     * @throws \ReflectionException|\Exception
     */
    public function getColumn(array $where, string $field, string $key = ''): array
    {
        // 使用 Eloquent 查询构建器获取字段数组
        $query = $this->getModel()->where($where);
        // 如果指定了键，则使用 keyBy 方法
        if ($key) {
            return $query->pluck($field, $key)->toArray();
        }
        // 否则，直接获取字段数组
        return $query->pluck($field)->toArray();
    }

    /**
     * 删除
     *
     * @param array|int|string $id
     * @param string|null      $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function delete(array|int|string $id, ?string $key = null): mixed
    {
        // 构建查询条件
        $where = is_array($id) ? $id : [is_null($key) ? $this->getPk() : $key => $id];

        // 使用 Eloquent 的 delete 方法删除记录
        return $this->getModel()->where($where)->delete();
    }

    /**
     * 删除记录
     *
     * @param mixed $id
     * @param bool  $force
     *
     * @return bool
     * @throws \Exception
     */
    public function destroy(mixed $id, bool $force = false): bool
    {
        // 使用 Eloquent 的 destroy 方法删除记录
        return $this->getModel()->destroy($id, $force) > 0;
    }

    /**
     * 更新
     *
     * @param string|int|array $id
     * @param array            $data
     * @param string|null      $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function update(string|int|array $id, array $data, ?string $key = null): mixed
    {
        // 构建查询条件
        $where = is_array($id) ? $id : [is_null($key) ? $this->getPk() : $key => $id];

        // 使用 Eloquent 的 update 方法更新记录
        return $this->getModel()->where($where)->update($data);
    }

    /**
     * setWhere
     *
     * @param             $where
     * @param string|null $key
     *
     * @return array
     * @throws \Exception
     */
    protected function setWhere($where, ?string $key = null): array
    {
        // 如果 $where 不是数组，则构建数组
        if (!is_array($where)) {
            $where = [is_null($key) ? $this->getPk() : $key => $where];
        }
        return $where;
    }

    /**
     * 批量更新
     *
     * @param array       $ids
     * @param array       $data
     * @param string|null $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function batchUpdate(array $ids, array $data, ?string $key = null): mixed
    {
        return $this->getModel()->whereIn(is_null($key) ? $this->getPk() : $key, $ids)->update($data);
    }

    /**
     * 保存返回模型
     *
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function save(array $data): mixed
    {
        return $this->getModel()->create($data);
    }

    /**
     * 批量插入
     *
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public function saveAll(array $data): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getModel()->insert($data);
    }

    /**
     * 获取某字段内的值
     *
     * @param             $value
     * @param string      $field
     * @param string|null $valueKey
     * @param array|null  $where
     *
     * @return mixed
     * @throws \Exception
     */
    public function getFieldValue($value, string $field, ?string $valueKey = null, ?array $where = []): mixed
    {
        // 如果提供了 $valueKey，则构建查询条件
        if ($valueKey) {
            $where[$valueKey] = $value; // 将 valueKey 和 value 加入条件
        } else {
            $where[$this->getPk()] = $value; // 默认使用主键作为条件
        }
        // 使用 Eloquent 查询构建器获取字段值
        return $this->getModel()->where($where)->value($field);
    }

    /**
     * 获取搜索器和搜索条件key,以及不在搜索器的条件数组
     *
     * @param array $where
     *
     * @return array[]
     * @throws \ReflectionException
     */
    private function getSearchData(array $where): array
    {
        $with       = [];
        $withValues = []; // 用于存储与 $with 对应的值
        $otherWhere = [];
        $model      = $this->getModel();
        $responses  = new \ReflectionClass($model);

        foreach ($where as $key => $value) {
            $method = 'scope' . Str::studly($key);
            if ($responses->hasMethod($method)) {
                $with[]           = $key; // 将搜索器方法的键加入 $with
                $withValues[$key] = $value; // 将对应的值存储到 $withValues
            } else {
                // 过滤不在搜索器中的条件
                if (!in_array($key, ['timeKey', 'store_stock', 'integral_time'])) {
                    if (!is_array($value)) {
                        $otherWhere[] = [$key, '=', $value]; // 单个条件
                    } elseif (count($value) === 3) {
                        $otherWhere[] = $value; // 复杂条件
                    }
                }
            }
        }
        return [$with, $withValues, $otherWhere]; // 返回 $with, $withValues 和 $otherWhere
    }

    /**
     * 根据搜索器获取内容
     *
     * @param array $where
     * @param bool  $search
     *
     * @return \Illuminate\Database\Query\Builder
     * @throws \Exception
     */
    protected function withSearchSelect(array $where, bool $search): mixed
    {
        [$with, $withValues, $otherWhere] = $this->getSearchData($where);
        $query = $this->getModel()->query();
        foreach ($with as $item) {
            $func = Str::studly($item);
            if (method_exists($this->getModel(), 'scope' . $func)) {
                $value = $withValues[$item] ?? null;
                if ($value !== null) {
                    $query->$func($value);
                }
            }
        }
        $filteredWhere = $this->filterWhere($otherWhere);
        if (!empty($filteredWhere)) {
            $query->where($filteredWhere);
        }
        return $query; // 返回查询构建器
    }

    /**
     * 过滤数据表中不存在的字段
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    protected function filterWhere(array $where = []): array
    {
        $fields = $this->getModel()->getFields(); // 获取模型的可填充字段
        foreach ($where as $key => $item) {
            // 检查键是否在可填充字段中
            if (!in_array($key, $fields)) {
                unset($where[$key]); // 过滤掉不存在的字段
            }
        }
        return $where; // 返回过滤后的条件
    }

    /**
     * 搜索
     *
     * @param array $where
     * @param bool  $search
     *
     * @return mixed
     * @throws \Exception
     */
    public function search(array $where = [], bool $search = true): mixed
    {
        if ($where) {
            return $this->withSearchSelect($where, $search); // 返回查询构建器
        } else {
            return $this->getModel(); // 返回模型实例
        }
    }

    /**
     * 求和
     *
     * @param array  $where
     * @param string $field
     * @param bool   $search
     *
     * @return float
     * @throws \Exception
     */
    public function sum(array $where, string $field, bool $search = false): float
    {
        // 构建查询
        $query = $this->getModel();
        // 应用搜索条件
        if ($search) {
            $query = $this->search($query, $where);
        } else {
            $query = $query->where($where);
        }
        // 计算总和并返回
        return (float)$query->sum($field);
    }

    /**
     * 高精度加法
     *
     * @param             $key
     * @param string      $incField
     * @param string      $inc
     * @param string|null $keyField
     * @param int         $acc
     *
     * @return bool
     */
    public function bcInc($key, string $incField, string $inc, string $keyField = null, int $acc = 2): bool
    {
        // 获取模型实例
        $model = $this->getModel();
        // 构建查询条件
        $query = $keyField ? $model->where($keyField, $key) : $model->where('id', $key);
        // 执行增量操作
        return $query->update([$incField => \DB::raw("COALESCE($incField, 0) + CAST($inc AS DECIMAL($acc, $acc))")]) > 0;
    }

    /**
     * 高精度 减法
     *
     * @param             $key
     * @param string      $decField
     * @param string      $dec
     * @param string|null $keyField
     * @param int         $acc
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function bcDec($key, string $decField, string $dec, string $keyField = null, int $acc = 2): bool
    {
        return $this->bc($key, $decField, $dec, $keyField, 2, $acc);
    }

    /**
     * 高精度计算并保存
     *
     * @param             $key
     * @param string      $field
     * @param string      $value
     * @param string|null $keyField
     * @param int         $type
     * @param int         $acc
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function bc($key, string $field, string $value, string $keyField = null, int $type = 1, int $acc = 2): bool
    {
        // 获取记录
        $result = $keyField === null ? $this->get($key) : $this->getOne([$keyField => $key]);
        if (!$result) return false;
        $newValue = 0;
        if ($type === 1) {
            // 加法
            $newValue = bcadd($result->{$field}, $value, $acc);
        } elseif ($type === 2) {
            // 减法
            if ($result->{$field} < $value) return false; // 检查是否足够减去
            $newValue = bcsub($result->{$field}, $value, $acc);
        }
        // 更新字段
        $result->{$field} = $newValue;
        // 保存更新
        return $result->save();
    }

    /**
     * 减库存加销量
     *
     * @param array  $where
     * @param int    $num
     * @param string $stock
     * @param string $sales
     *
     * @return bool
     * @throws \Exception
     */
    public function decStockIncSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales'): bool
    {
        $product = $this->getModel()->where($where)->first();
        if ($product) {
            return $this->getModel()->where($where)->decrement($stock, $num)->increment($sales, $num);
        }
        return false;
    }

    /**
     * 加库存减销量
     *
     * @param array  $where
     * @param int    $num
     * @param string $stock
     * @param string $sales
     *
     * @return bool
     * @throws \Exception
     */
    public function incStockDecSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales'): bool
    {
        $product = $this->getModel()->where($where)->first();
        if ($product) {
            return $this->getModel()->where($where)->increment($stock, $num)->decrement($sales, $num);
        }
        return true;
    }

    /**
     * 获取条件数据中的某个值的最大值
     *
     * @param array  $where
     * @param string $field
     *
     * @return mixed
     */
    public function getMax(array $where = [], string $field = ''): mixed
    {
        return $this->getModel()->where($where)->max($field);
    }

    /**
     * 获取条件数据中的某个值的最小值
     *
     * @param array  $where
     * @param string $field
     *
     * @return mixed
     */
    public function getMin(array $where = [], string $field = ''): mixed
    {
        return $this->getModel()->where($where)->min($field);
    }

}
