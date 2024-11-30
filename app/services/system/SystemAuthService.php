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
use app\model\tp\system\SystemMenu as TpSystemMenu;
use app\model\la\system\SystemMenu as LaSystemMenu;
use app\model\tp\system\SystemUser as TpSystemUser;
use app\model\la\system\SystemUser as LaSystemUser;
use madong\basic\BaseService;
use madong\exception\AuthException;
use madong\services\cache\CacheService;
use madong\utils\Dict;
use madong\utils\JwtAuth;
use madong\utils\Tree;
use support\Container;
use support\Request;

class SystemAuthService extends BaseService
{

    public function __construct()
    {
        $this->dao = Container::make(SystemUserDao::class);
    }

    /**
     * 通过token 获取用户信息
     *
     * @param string $token
     * @param int    $code
     *
     * @return array
     */
    public function parseToken(string $token, int $code = 401): array
    {
        try {

            $cache = Container::make(CacheService::class);
            if (empty($token) || $token === 'undefined') {
                throw new AuthException('Token is required.', $code);
            }
            $jwtAuth = Container::make(JwtAuth::class);
            [$id, $type] = $jwtAuth->parseToken($token);
            $prefix = "token_" . $type . '_';
            if (!$cache->get($prefix . md5($token))) {
                throw new AuthException('登录状态已过期，需要重新登录md00001', $code);
            }
            $adminInfo = $this->dao->get($id, ['*'], ['roles', 'posts', 'depts']);
            if (empty($adminInfo) || empty($adminInfo->getData('id'))) {
                throw new AuthException('用户不存在或禁用ad00001', $code);
            }

            $depts = $adminInfo->getData('depts');
            if (!empty($depts)) {
                $adminInfo->set('dept_name', $depts->getData('name'));
            }

            $roles = $adminInfo->getData('roles');
            if (!empty($roles)) {
                $adminInfo->set('role_names', array_column($roles->toArray(), 'name'));
            }
            $adminInfo->set('login_date', date('Y-m-d H:i:s', $adminInfo->getData('login_time')));
            $adminInfo->set('type', $type);
            $avatar = $this->getAvatarUrl($adminInfo);
            $adminInfo->set('avatar', $avatar);
            return $adminInfo->hidden(['password', 'delete_time', 'status'])->toArray();
        } catch (\Throwable $e) {
            throw new AuthException($e->getMessage(), 401);
        }
    }

    /**
     * 权限验证
     *
     * @param \support\Request $request
     *
     * @return bool
     */
    public function verifyAuth(Request $request): bool
    {
        try {
            // 获取当前的接口和接口类型
            $rule   = $request->path();
            $method = strtolower(trim($request->method()));

            // 判断接口是特定几种时放行
            if (in_array($rule, ['/system/auth/user-info', '/system/logout', '/system/auth/user-menus', '/system/auth/perm-code', '/system/dict/get-by-dict-type'])) {
                return true;
            }

            // 获取所有接口类型及对应的接口
            $systemMenuService = Container::make(SystemMenuService::class);
            $allAuth           = $this->getAllAuth($systemMenuService);
            // 权限菜单未添加时放行
            if (!isset($allAuth[$method]) || !in_array($rule, $allAuth[$method])) {
                // 获取管理员角色权限
                $roleIds = $this->getRoleIds($request);
                $menuIds = $this->getMenuIdsByRoleIds($roleIds, $systemMenuService);
                $allAuth = $this->getAllAuth($systemMenuService, $menuIds);
                if (!isset($allAuth[$method]) || !in_array($rule, $allAuth[$method])) {
                    throw new AuthException('您暂时没有访问权限', 401);
                }
            }
            return true;
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage(), 401);
        }
    }

    /**
     * 获取后台管理菜单
     *
     * @param array $where
     *
     * @return array
     */
    public function getAllMenus(array $where = []): array
    {
        /**@var SystemMenuService  $systemMenuService */
        $systemMenuService = Container::make(SystemMenuService::class);
        $list  = $systemMenuService->dao->selectList($where, '*', 0, 0, 'sort asc', [], true);
        //兼容LaORM
        if ($list instanceof \Illuminate\Database\Eloquent\Collection) {
            $list->makeVisible(['id', 'pid', 'type', 'sort', 'redirect', 'path', 'name', 'meta', 'component']);
        } else {
            //默认tp模型
            foreach ($list as $item) {
                $item->set('name', $item->getData('code'));
                $item->set('meta', $item->meta);
            }
            $list->visible(['id', 'pid', 'type', 'sort', 'redirect', 'path', 'name', 'meta', 'component']);
        }

        $tree = new Tree($list);
        return $tree->getTree();
    }

    /**
     * 获取用户角色获取对应的菜单
     *
     * @param \madong\utils\Dict $userDict
     *
     * @return array
     */
    public function getMenusByUserRoles(Dict $userDict): array
    {
        try {
            $role    = $userDict->get('roles', []);
            $roleIds = array_column($role, 'id');
            if (empty($roleIds)) {
                return [];
            }
            $systemRoleServices = new SystemRoleService();
            $menuIds            = $systemRoleServices->getMenuIdsByRoleIds($roleIds);
            $systemMenuService  = Container::make(SystemMenuService::class);
            $filteredMenuIds    = $systemMenuService->filterMenuIds($menuIds); // 菜单 ID 集合
            if (empty($filteredMenuIds)) {
                return [];
            }
            $map1 = [
                'type'    => [1, 2],//目录&菜单类型
                'enabled' => 1,
                'id'      => $filteredMenuIds,
            ];
            return $this->getAllMenus($map1);
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }
    }

    /**
     * 获取用户角色-菜单权限码
     *
     * @param \madong\utils\Dict $userDict
     *
     * @return array
     */
    public function getCodesByUserRoles(Dict $userDict): array
    {
        try {
            $role    = $userDict->get('roles', []);
            $roleIds = array_column($role, 'id');
            if (empty($roleIds)) {
                return [];
            }
            $systemRoleServices = new SystemRoleService();
            $menuIds            = $systemRoleServices->getMenuIdsByRoleIds($roleIds);
            $systemMenuService  = Container::make(SystemMenuService::class);
            $filteredMenuIds    = $systemMenuService->filterMenuIds($menuIds); // 菜单 ID 集合
            if (empty($filteredMenuIds)) {
                return [];
            }
            $map1 = [
                'type'    => 3,//按钮
                'enabled' => 1,
                'id'      => $filteredMenuIds,
            ];
            return $systemMenuService->getColumn($map1, 'code');
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }
    }

//    /**
//     * 获取角色-菜单id集合
//     *
//     * @param string|int $roleId
//     *
//     * @return array
//     */
//    public function getMenuIdsByRole(string|int $roleId): array
//    {
//        $systemRoleService = Container::make(SystemRoleService::class);
//        $systemMenuService = Container::make(SystemMenuService::class);
//        $roleData          = $systemRoleService->get($roleId, ['*'], ['menus' => function (Query $query) {
//            $query->where('enabled', 1)->order('sort', 'asc');
//        }]);
//        $data              = [];
//        if (empty($roleData)) {
//            return $data;
//        }
//        $isSuperAdmin = $roleData->getData('is_super_admin');
//        if ($isSuperAdmin === 1) {
//            //超级管理员角色提供所有菜单
//            $data = $systemMenuService->getColumn([], 'id');
//        }
//        if ($isSuperAdmin !== 1) {
//            //普通管理员提供设定的权限id集合
//            $menuData = $roleData->getData('menus');
//            if (!empty($menuData)) {
//                $data = array_column($menuData->toArray(), 'id');
//            }
//        }
//        return $data ?? [];
//    }

    /**
     * 获取所有权限 || 根据$menuIds获取对应权限
     *
     * @param \app\services\system\SystemMenuService $systemMenuService
     * @param array                                  $menuIds
     *
     * @return array
     */
    private function getAllAuth(SystemMenuService $systemMenuService, array $menuIds = []): array
    {
        $map = [['path', '<>', ''], ['type', '<>', 3]];
        if (!empty($menuIds)) {
            $map[] = ['id', 'in', $menuIds];
        }
        $allList = $systemMenuService->getColumn($map, 'path,methods');

        $allAuth = [];
        foreach ($allList as $item) {
            $methodKey             = strtolower(trim($item['methods']));
            $pathKey               = strtolower(trim(str_replace(' ', '', $item['path'])));
            $allAuth[$methodKey][] = $pathKey;
        }

        return $allAuth;
    }

    /**
     * 通过用户角色筛选ID
     *
     * @param \support\Request $request
     *
     * @return array
     */
    private function getRoleIds(Request $request): array
    {
        $dict = new Dict();
        $dict->putAll($request->adminInfo());
        $roles = $dict->get('roles', []);
        return array_column($roles, 'id');
    }

    /**
     * 获取菜单ids
     *
     * @param array                                 $roleIds
     * @param \app\service\system\SystemMenuService $systemMenuService
     *
     * @return array
     */
    private function getMenuIdsByRoleIds(array $roleIds, SystemMenuService $systemMenuService): array
    {
        $systemRoleService = new SystemRoleService();
        $data              = $systemRoleService->getMenuIdsByRoleIds($roleIds);
        return $systemMenuService->filterMenuIds($data);
    }

    /**
     * 获取头像
     *
     * @param TpSystemUser|LaSystemUser $adminInfo
     *
     * @return string
     */
    private function getAvatarUrl(TpSystemUser|LaSystemUser $adminInfo): string
    {
        /**@var SystemConfigService  $systemConfigService */
        $systemConfigService = Container::make(SystemConfigService::class);
        $url                 = $systemConfigService->getConfig('site_url', 'site_setting');
        if (empty($url)) {
            $url        = config('process.webman.listen');
            $parsed_url = parse_url($url);
            $port       = $parsed_url['port'] ?? 8787; // 使用 null 合并运算符
            $url        = 'http://127.0.0.1:' . $port;
        }
        if (!str_ends_with($url, '/')) {
            $url .= '/';
        }
        $avatar = 'upload/avatar.jpg';
        if (!empty($adminInfo->getData('avatar'))) {
            $avatar = $adminInfo->getData('avatar');
        }
        return $url . $avatar;
    }
}
