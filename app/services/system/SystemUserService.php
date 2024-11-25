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

use app\dao\system\SystemUserDao;
use app\model\tp\system\SystemUser;
use madong\basic\BaseService;
use madong\exception\AdminException;
use madong\services\cache\CacheService;
use madong\utils\JwtAuth;
use support\Container;
use Webman\Event\Event;

class SystemUserService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemUserDao::class);
    }

    /**
     * 用户详情
     *
     * @param $id
     *
     * @return SystemUser|null
     */
    public function get($id): SystemUser|null
    {
        /** @var SystemUser|null $model */
        $model = $this->dao->get($id, ['*'], ['roles', 'posts', 'depts']);
//        var_dump($model->getLastSql());
        if (!empty($model)) {
            $roles = $model->getData('roles');
            $posts = $model->getData('posts');
            $model->set('role_id_list', []);
            $model->set('post_id_list', []);
            if (!empty($roles)) {
                $model->set('role_id_list', array_column($roles->toArray(), 'id'));
            }
            if (!empty($posts)) {
                $model->set('post_id_list', array_column($posts->toArray(), 'id'));
            }
            $model->hidden(['password']);
        }
        return $model;
    }

    /**
     * selectList
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     * @param string $order
     * @param array  $with
     * @param bool   $search
     *
     * @return \think\Collection|null
     */
    public function selectList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '', array $with = [], bool $search = false)
    {
        $where['enabled'] = 1;
        return $this->dao->selectList($where, $field, $page, $limit, $order, ['depts', 'posts', 'roles'], $search);
    }

    /**
     * save
     *
     * @param array $data
     *
     * @return SystemUser|null
     */
    public function save(array $data): SystemUser|null
    {
        try {
            return $this->transaction(function () use ($data) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $roles            = $data['role_id_list'] ?? [];
                $posts            = $data['post_id_list'] ?? [];
                $model            = $this->dao->save($data);
                if (!empty($roles)) {
                    $model->roles()->saveAll($roles);
                }
                if (!empty($posts)) {
                    $model->posts()->save($posts);
                }
                return $model;
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 编辑
     *
     * @param $id
     * @param $data
     *
     * @return void
     */
    public function update($id, $data): void
    {
        try {
            $this->transaction(function () use ($id, $data) {
                unset($data['password']);
                $roles  = $data['role_id_list'] ?? [];
                $posts  = $data['post_id_list'] ?? [];
                $result = $this->dao->update(['id' => $id], $data);
                $user   = $this->dao->get($id);
                if ($result && $user) {
                    $user->roles()->detach();
                    $user->posts()->detach();
                    if (!empty($roles)) {
                        $user->roles()->saveAll($roles);
                    }
                    if (!empty($posts)) {
                        $user->posts()->save($posts);
                    }
                }
            });
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * destroy
     *
     * @param $id
     * @param $force
     *
     * @return mixed
     */
    public function destroy($id, $force): mixed
    {
        $ret = $this->dao->count([['id', 'in', $id], ['is_super', '=', 1]]);
        if ($ret > 0) {
            throw new AdminException('系统内置用户，不允许删除');
        }
        return $this->dao->destroy($id);
    }

    /**
     * 用户-冻结
     *
     * @param array|string $id
     */
    public function locked(array|string $id): void
    {
        try {
            if (is_string($id)) {
                $id = array_map('trim', explode(',', $id));
            }
            $ret = $this->dao->count([['id', 'in', $id], ['is_super', '=', 1]]);
            if ($ret > 0) {
                throw new AdminException('系统内置用户，不允许冻结');
            }
            $this->dao->batchUpdate($id, ['is_locked' => 1]);
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 用户-解除冻结
     *
     * @param array|string $id
     */
    public function unLocked(array|string $id): void
    {
        try {
            if (is_string($id)) {
                $id = array_map('trim', explode(',', $id));
            }
            $this->dao->batchUpdate($id, ['is_locked' => 0]);
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 用户登录
     *
     * @param string      $username
     * @param string|null $password
     * @param string      $type
     * @param string      $grantType
     *
     * @return array
     */
    public function login(string $username, string $password = '', string $type = 'admin', string $grantType = 'default'): array
    {
        $adminInfo = $this->dao->get(['user_name' => $username]);
        $status    = 1;
        $message   = '登录成功';
        if (!$adminInfo) {
            $status  = 0;
            $message = '账号或密码错误，请重新输入!';
            throw new AdminException($message);
        }
        if ($adminInfo->status === 2) {
            $status  = 0;
            $message = '您已被禁止登录!';
        }
        $noPassword = ['sms', 'refresh_token'];//特殊第三方登录不验证密码
        if (!in_array($grantType, $noPassword) && !password_verify($password, $adminInfo->password)) {
            $status  = 0;
            $message = '账号或密码错误，请重新输入!';
        }
        if ($status === 0) {
            // 登录事件
            Event::emit('user.login', compact('username', 'status', 'message'));
            throw new AdminException($message);
        }
        $adminInfo->login_time = time();
        $adminInfo->login_ip   = request()->getRealIp();
        $adminInfo->save();

        $jwt   = new JwtAuth();
        $token = $jwt->createToken($adminInfo->id, $adminInfo->user_name, $type);
        // 登录事件
        Event::emit('user.login', compact('username', 'status', 'message', 'token', 'type'));

        return [
            'token'         => $token['token'],
            'user_id'       => $adminInfo->id,
            'refresh_token' => '',
            'expires_time'  => $token['params']['exp'],
        ];
    }

    /**
     * 获取用户列表-角色id
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     *
     * @return array
     */
    public function getUsersListByRoleId(array $where, string $field, int $page, int $limit): array
    {
        $roleId = $where['role_id'];
        //1.0 获取总数
        $total = $this->dao->getModel()->hasWhere('userRoles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->when(!empty($where), function ($query) use ($where) {
            unset($where['role_id']);
            $query->where($where);
        })->count();

        //2.0 获取列表
        $items = $this->dao->getModel()->hasWhere('userRoles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(!empty($where), function ($query) use ($where) {
            unset($where['role_id']);
            $query->where($where);
        })->select()->toArray();

        return compact('total', 'items');
    }

    /**
     * 排除角色ID-用户列表
     *
     * @param array  $where
     * @param string $field
     * @param int    $page
     * @param int    $limit
     *
     * @return array
     */
    public function getUsersExcludingRole(array $where, string $field, int $page, int $limit): array
    {
        $roleId = $where['role_id'];

        /**@var SystemUserRoleService $systemUserRoleService */
        $systemUserRoleService = Container::make(SystemUserRoleService::class);

        $excludUserIds = $systemUserRoleService->getColumn(['role_id' => $roleId], 'user_id');

        // 1.0 获取总数
        $total = $this->dao->getModel()
            ->with(['roles'])
            ->whereNotIn('id', $excludUserIds)
            ->when(!empty($where), function ($query) use ($where) {
                unset($where['role_id']);
                $query->where($where);
            })
            ->count();

        // 2.0 获取列表
        $items = $this->dao->getModel()
            ->with(['roles'])
            ->whereNotIn('id', $excludUserIds)
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->when(!empty($where), function ($query) use ($where) {
                unset($where['role_id']);
                $query->where($where);
            })
            ->select()
            ->toArray();

        return compact('total', 'items');
    }

    /**
     * 更新用户信息
     *
     * @param string|int $id
     * @param array      $data
     *
     * @return mixed
     */
    public function updateUserInfo(string|int $id, array $data)
    {
        return $this->dao->update(['id' => $id], $data);
    }

    /**
     * 更新用户头像
     *
     * @param string|int $id
     * @param string     $avatar
     *
     * @return mixed
     */
    public function updateAvatarUser(string|int $id, string $avatar)
    {
        return $this->dao->update(['id' => $id], ['avatar' => $this->getPathFromUrl($avatar)]);
    }

    /**
     * 更新个人用户密码
     *
     * @param string|int $id
     * @param array      $data
     *
     * @return mixed
     */
    public function updateUserPwd(string|int $id, array $data): void
    {
        $this->transaction(function () use ($id, $data) {
            $info = $this->dao->get($id);
            if (empty($info)) {
                throw new AdminException('用户不存在或被删除');
            }
            if (!password_verify($data['password'], $info->password)) {
                throw new AdminException('旧密码错误，请重新输入!');
            }
            return $this->dao->update(['id' => $id], ['password' => $this->passwordHash($data['new_password'])]);
        });
    }

    /**
     * 强制下线
     *
     * @param $data
     */
    public function kickoutByTokenValueUser($data): void
    {
        $this->transaction(function () use ($data) {
            $systemLoginLogService = Container::make(SystemLoginLogService::class);
            $cacheService          = Container::make(CacheService::class);
            $systemLoginLogService->update([['key', 'in', $data]], ['expires_time' => time() - 1, 'remark' => '强制下线']);
            foreach ($data as $key => $value) {
                $cacheService->delete($value);
            }
        });
    }

    /**
     * 入参移除url
     *
     * @param string $url
     *
     * @return string
     */
    private function getPathFromUrl(string $url): string
    {
        // 如果没有协议，添加 http:// 作为默认
        if (!preg_match('#^https?://#', $url)) {
            $url = 'http://' . $url; // 或者根据需要选择 https
        }
        preg_match('#^https?://[^/]+(/.*)$#', $url, $matches);
        return isset($matches[1]) ? ltrim($matches[1], '/') : '';
    }
}
