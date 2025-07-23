<?php

/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: https://madong.tech
 */

namespace core\casbin\adapter;

use Casbin\Model\Model;
use Casbin\Persist\Adapter;
use Casbin\Persist\AdapterHelper;
use Casbin\Persist\UpdatableAdapter;
use Casbin\Persist\BatchAdapter;
use Casbin\Persist\FilteredAdapter;
use Casbin\Persist\Adapters\Filter;
use Casbin\Exceptions\InvalidFilterTypeException;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use core\casbin\model\RuleModel;
use Throwable;

/**
 * Laravel-ORM适配器
 *
 * @author Mr.April
 * @since  1.0
 */
class DatabaseAdapter implements Adapter, UpdatableAdapter, BatchAdapter, FilteredAdapter
{
    use AdapterHelper;

    /**
     * @var bool
     */
    private bool $filtered = false;

    /**
     * RuleModel model.
     *
     * @var RuleModel
     */
    protected RuleModel $model;

    /**
     * LaravelDatabaseAdapter constructor.
     *
     * @param string|null $driver
     */
    public function __construct(?string $driver = null)
    {
        $this->model = new RuleModel([], $driver);
    }

    /**
     * Filter the rule.
     *
     * @param array $rule
     *
     * @return array
     */
    public function filterRule(array $rule): array
    {
        $rule = array_values($rule);

        $i = count($rule) - 1;
        for (; $i >= 0; $i--) {
            if ($rule[$i] != '' && !is_null($rule[$i])) {
                break;
            }
        }
        return array_slice($rule, 0, $i + 1);
    }

    /**
     * savePolicyLine function.
     *
     * @param string $ptype
     * @param array  $rule
     *
     * @return void
     */
    public function savePolicyLine(string $ptype, array $rule): void
    {
        $data = ['ptype' => $ptype];

        // 处理标准字段 v0-v5
        foreach ($rule as $key => $value) {
            if (is_numeric($key)) {
                $data['v' . $key] = $value;
            }
        }

        // 处理 trace_type 追踪类型字段
//        if (isset($rule[0])) {
//            // 自动推断类型（如 v0=user:alice → trace_type=user）
//            if (str_starts_with($rule[0], 'user:')) {
//                $data['trace_type'] = 'user';
//                $data['v0']         = substr($rule[0], 5); // 去掉前缀
//            }
//            if (str_starts_with($rule[0], 'role:')) {
//                $data['trace_type'] = 'role';
//                $data['v0']         = substr($rule[0], 5); // 去掉前缀
//            }
//        }
        // 使用 updateOrCreate 避免重复
        $this->model->updateOrCreate($data);
    }

    /**
     * loads all policy rules from the storage.
     *
     * @param Model $model
     */
    public function loadPolicy(Model $model): void
    {
        $rows = $this->model->select(['ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5'])->get()->toArray();
        foreach ($rows as $row) {
            $this->loadPolicyArray($this->filterRule($row), $model);
        }
    }

    /**
     * saves all policy rules to the storage.
     *
     * @param Model $model
     */
    public function savePolicy(Model $model): void
    {
        foreach ($model['p'] as $ptype => $ast) {
            foreach ($ast->policy as $rule) {
                $this->savePolicyLine($ptype, $rule);
            }
        }

        foreach ($model['g'] as $ptype => $ast) {
            foreach ($ast->policy as $rule) {
                $this->savePolicyLine($ptype, $rule);
            }
        }
    }

    /**
     * adds a policy rule to the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param array  $rule
     */
    public function addPolicy(string $sec, string $ptype, array $rule): void
    {
        $this->savePolicyLine($ptype, $rule);
    }

    /**
     * Adds a policy rules to the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string     $sec
     * @param string     $ptype
     * @param string[][] $rules
     */
    public function addPolicies(string $sec, string $ptype, array $rules): void
    {
        foreach ($rules as $rule) {
            $temp = ['ptype' => $ptype];
            foreach ($rule as $key => $value) {
                $temp['v' . $key] = $value;
            }
            $this->model->updateOrCreate($temp);
        }
    }

    /**
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param array  $rule
     */
    public function removePolicy(string $sec, string $ptype, array $rule): void
    {
        $instance = $this->model->where('ptype', $ptype);
        foreach ($rule as $key => $value) {
            $instance->where('v' . $key, $value);
        }
        $data = $instance->get();
        foreach ($data as $item) {
            $item->delete();
        }
    }

    /**
     * @param string      $sec
     * @param string      $ptype
     * @param int         $fieldIndex
     * @param string|null ...$fieldValues
     *
     * @return array
     * @throws Throwable
     */
    public function _removeFilteredPolicy(string $sec, string $ptype, int $fieldIndex, ?string ...$fieldValues): array
    {
        $removedRules = [];
        $data         = $this->getCollection($ptype, $fieldIndex, $fieldValues);

        foreach ($data as $model) {
            $item           = $model->hidden(['id', 'ptype'])->toArray();
            $item           = $this->filterRule($item);
            $removedRules[] = $item;
        }

        return $removedRules;
    }

    /**
     * Removes policy rules from the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string     $sec
     * @param string     $ptype
     * @param string[][] $rules
     */
    public function removePolicies(string $sec, string $ptype, array $rules): void
    {
        DB::transaction(function () use ($sec, $ptype, $rules) {
            foreach ($rules as $rule) {
                $this->removePolicy($sec, $ptype, $rule);
            }
        });
    }

    /**
     * RemoveFilteredPolicy removes policy rules that match the filter from the storage.
     * This is part of the Auto-Save feature.
     *
     * @param string $sec
     * @param string $ptype
     * @param int    $fieldIndex
     * @param string ...$fieldValues
     */
    public function removeFilteredPolicy(string $sec, string $ptype, int $fieldIndex, string ...$fieldValues): void
    {
        $data = $this->getCollection($ptype, $fieldIndex, $fieldValues);
        foreach ($data as $item) {
            $item->delete();
        }
    }

    /**
     * Updates a policy rule from storage.
     * This is part of the Auto-Save feature.
     *
     * @param string   $sec
     * @param string   $ptype
     * @param string[] $oldRule
     * @param string[] $newPolicy
     */
    public function updatePolicy(string $sec, string $ptype, array $oldRule, array $newPolicy): void
    {
        $instance = $this->model->where('ptype', $ptype);
        foreach ($oldRule as $key => $value) {
            $instance->where('v' . $key, $value);
        }
        $instance = $instance->first();

        $update = [];
        foreach ($newPolicy as $key => $value) {
            $update['v' . $key] = $value;
        }

        $instance->fill($update);
        $instance->save();
    }

    /**
     * UpdatePolicies updates some policy rules to storage, like DB, redis.
     *
     * @param string     $sec
     * @param string     $ptype
     * @param string[][] $oldRules
     * @param string[][] $newRules
     *
     * @return void
     */
    public function updatePolicies(string $sec, string $ptype, array $oldRules, array $newRules): void
    {
        DB::transaction(function () use ($sec, $ptype, $oldRules, $newRules) {
            foreach ($oldRules as $i => $oldRule) {
                $this->updatePolicy($sec, $ptype, $oldRule, $newRules[$i]);
            }
        });
    }

    /**
     * UpdateFilteredPolicies deletes old rules and adds new rules.
     *
     * @param string  $sec
     * @param string  $ptype
     * @param array   $newPolicies
     * @param integer $fieldIndex
     * @param string  ...$fieldValues
     *
     * @return array
     */
    public function updateFilteredPolicies(string $sec, string $ptype, array $newPolicies, int $fieldIndex, string ...$fieldValues): array
    {
        $oldRules = [];
        DB::transaction(function () use ($sec, $ptype, $fieldIndex, $fieldValues, $newPolicies, &$oldRules) {
            $oldRules = $this->_removeFilteredPolicy($sec, $ptype, $fieldIndex, ...$fieldValues);
            $this->addPolicies($sec, $ptype, $newPolicies);
        });
        return $oldRules;
    }

    /**
     * Returns true if the loaded policy has been filtered.
     *
     * @return bool
     */
    public function isFiltered(): bool
    {
        return $this->filtered;
    }

    /**
     * Sets filtered parameter.
     *
     * @param bool $filtered
     */
    public function setFiltered(bool $filtered): void
    {
        $this->filtered = $filtered;
    }

    /**
     * Loads only policy rules that match the filter.
     *
     * @param Model $model
     * @param mixed $filter
     *
     * @throws InvalidFilterTypeException
     */
    public function loadFilteredPolicy(Model $model, $filter): void
    {
        $instance = $this->model;
        if (is_string($filter)) {
            $instance->whereRaw($filter);
        } elseif ($filter instanceof Filter) {
            $where = [];
            foreach ($filter->p as $k => $v) {
                $where[$v] = $filter->g[$k];
            }
            $instance->where($where);
        } elseif ($filter instanceof Closure) {
            $instance = $instance->where($filter);
        } else {
            throw new InvalidFilterTypeException('invalid filter type');
        }
        $rows = $instance->get()->makeHidden(['created_at', 'updated_at', 'id'])->toArray();
        if ($rows) {
            foreach ($rows as $row) {
                $row  = array_filter($row, function ($value) {
                    return !is_null($value) && $value !== '';
                });
                $line = implode(
                    ', ',
                    array_filter($row, function ($val) {
                        return '' != $val && !is_null($val);
                    })
                );
                $this->loadPolicyLine(trim($line), $model);
            }
        }
        $this->setFiltered(true);
    }

    /**
     * @param string $ptype
     * @param int    $fieldIndex
     * @param array  $fieldValues
     *
     * @return Builder[]|Collection
     */
    protected function getCollection(string $ptype, int $fieldIndex, array $fieldValues): Collection|array
    {
        $where = [
            'ptype' => $ptype,
        ];
        foreach (range(0, 5) as $value) {
            if ($fieldIndex <= $value && $value < $fieldIndex + count($fieldValues)) {
                if ('' != $fieldValues[$value - $fieldIndex]) {
                    $where['v' . $value] = $fieldValues[$value - $fieldIndex];
                }
            }
        }

        return $this->model->where($where)->get();
    }
}
