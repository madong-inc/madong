<?php
/**
 *+------------------
 * madong
 *+------------------
 * Copyright (c) https://gitee.com/motion-code  All rights reserved.
 *+------------------
 * Author: Mr. April (405784684@qq.com)
 *+------------------
 * Official Website: http://www.madong.cn
 */

use support\Request;
use Webman\Route;

/*
 * 无需授权的接口
 */
Route::group(function () {
    Route::post('/system/login', [\app\admin\controller\LoginController::class, 'login'])->name('登录');
    Route::post('/system/logout', [\app\admin\controller\LoginController::class, 'logout'])->name('注销');
    Route::get('/auth/captcha', [\app\admin\controller\LoginController::class, 'captcha'])->name('验证码');
});

Route::group('/system', function () {
    /**
     * 用户模块
     */
    Route::group(function () {
        Route::get('/user', [\app\admin\controller\system\SystemUserController::class, 'index'])->name('列表');
        Route::get('/user/{id}', [\app\admin\controller\system\SystemUserController::class, 'show'])->name('详情');
        Route::put('/user/{id}', [\app\admin\controller\system\SystemUserController::class, 'update'])->name('更新');
        Route::put('/user/{id}/recovery', [\app\admin\controller\system\SystemUserController::class, 'recovery'])->name('恢复');
        Route::post('/user', [\app\admin\controller\system\SystemUserController::class, 'store'])->name('保存');
        Route::post('/user-select', [\app\admin\controller\system\SystemUserController::class, 'select'])->name('下拉列表');
        Route::delete('/user/{id}', [\app\admin\controller\system\SystemUserController::class, 'destroy'])->name('删除');

        Route::post('/user/update-avatar', [\app\admin\controller\system\SystemUserController::class, 'dev'])->name('修改头像');
        Route::post('/user/update-info', [\app\admin\controller\system\SystemUserController::class, 'dev'])->name('修改个人信息');
        Route::post('/user/update-pwd', [\app\admin\controller\system\SystemUserController::class, 'dev'])->name('修改个人密码');
        Route::post('/user/reset-password', [\app\admin\controller\system\SystemUserController::class, 'changePassword'])->name('重置密码');
        Route::post('/user/locked', [\app\admin\controller\system\SystemUserController::class, 'locked'])->name('锁定用户');
        Route::post('/user/un-locked', [\app\admin\controller\system\SystemUserController::class, 'unLocked'])->name('取消锁定用户');
        Route::post('/user/grant-role', [\app\admin\controller\system\SystemUserController::class, 'grantRole'])->name('授权角色');

    });

    /**
     * Auth
     */
    Route::group(function () {
        Route::get('/auth/user-info', [\app\admin\controller\system\SystemAuthController::class, 'getUserInfo'])->name('获取用户详情');
        Route::get('/auth/user-menus', [\app\admin\controller\system\SystemAuthController::class, 'getUserMenus'])->name('获取用户菜单');
        Route::get('/auth/perm-code', [\app\admin\controller\system\SystemAuthController::class, 'getUserCodes'])->name('权限码');
        Route::get('/auth/role-menu-ids', [\app\admin\controller\system\SystemAuthController::class, 'roleMenuIds'])->name('根据角色ID获取菜单ID集合');
        Route::get('/auth/role-menu-list', [\app\admin\controller\system\SystemAuthController::class, 'dev'])->name('获取角色菜单列表');
        Route::get('/auth/role-menu-tree', [\app\admin\controller\system\SystemAuthController::class, 'dev'])->name('获取角色菜单树');
        Route::get('/auth/user-list-by-role-id', [\app\admin\controller\system\SystemAuthController::class, 'getUsersByRoleId'])->name('通过角色ID获取用户列表');
        Route::get('/auth/user-list-exclude-role-id', [\app\admin\controller\system\SystemAuthController::class, 'getUsersExcludingRole'])->name('获取用户列表-排除指定角色');
        Route::post('/auth/save-role-menu', [\app\admin\controller\system\SystemAuthController::class, 'saveRoleMenuRelation'])->name('保存角色菜单关系');
        Route::post('/auth/save-user-role', [\app\admin\controller\system\SystemAuthController::class, 'saveUserRoles'])->name('添加用户角色关系');
        Route::post('/auth/remove-user-role', [\app\admin\controller\system\SystemAuthController::class, 'removeUserRole'])->name('删除用户角色关系');
    });

    /**
     * 字典
     */
    Route::group(function () {
        Route::get('/dict', [\app\admin\controller\system\SystemDictController::class, 'index'])->name('列表');
        Route::get('/dict/{id}', [\app\admin\controller\system\SystemDictController::class, 'show'])->name('详情');
        Route::put('/dict/{id}', [\app\admin\controller\system\SystemDictController::class, 'update'])->name('更新');
        Route::post('/dict', [\app\admin\controller\system\SystemDictController::class, 'store'])->name('保存');
        Route::delete('/dict/{id}', [\app\admin\controller\system\SystemDictController::class, 'destroy'])->name('删除');
        Route::post('/dict/enum-dict-list', [\app\admin\controller\system\SystemDictController::class, 'enumDictList'])->name('枚举字典');
        Route::post('/dict/custom-dict-list', [\app\admin\controller\system\SystemDictController::class, 'customDictList'])->name('自定义字典');
        Route::post('/dict/get-by-dict-type', [\app\admin\controller\system\SystemDictController::class, 'getByDictType'])->name('根据字典编码获取字典');
    });

    /**
     * 字典数据
     */
    Route::group(function () {
        Route::get('/dict-item', [\app\admin\controller\system\SystemDictItemController::class, 'index'])->name('列表');
        Route::get('/dict-item/{id}', [\app\admin\controller\system\SystemDictItemController::class, 'show'])->name('详情');
        Route::put('/dict-item/{id}', [\app\admin\controller\system\SystemDictItemController::class, 'update'])->name('更新');
        Route::post('/dict-item', [\app\admin\controller\system\SystemDictItemController::class, 'store'])->name('保存');
        Route::delete('/dict-item/{id}', [\app\admin\controller\system\SystemDictItemController::class, 'destroy'])->name('删除');
    });

    /**
     * 菜单
     */
    Route::group(function () {
        Route::get('/menu', [\app\admin\controller\system\SystemMenuController::class, 'index'])->name('列表');
        Route::get('/menu/{id}', [\app\admin\controller\system\SystemMenuController::class, 'show'])->name('详情');
        Route::put('/menu/{id}', [\app\admin\controller\system\SystemMenuController::class, 'update'])->name('更新');
        Route::post('/menu', [\app\admin\controller\system\SystemMenuController::class, 'store'])->name('保存');
        Route::delete('/menu/{id}', [\app\admin\controller\system\SystemMenuController::class, 'destroy'])->name('删除');
//        Route::post('/menu/tree', [\app\admin\controller\system\SystemMenuController::class, 'buildMenuTree'])->name('菜单Tree');
        Route::post('/menu/app-list', [\app\admin\controller\system\SystemMenuController::class, 'dev'])->name('应用列表');
    });

    /**
     * 角色
     */
    Route::group(function () {
        Route::get('/role', [\app\admin\controller\system\SystemRoleController::class, 'index'])->name('列表');
        Route::get('/role/{id}', [\app\admin\controller\system\SystemRoleController::class, 'show'])->name('详情');
        Route::put('/role/{id}', [\app\admin\controller\system\SystemRoleController::class, 'update'])->name('更新');
        Route::post('/role', [\app\admin\controller\system\SystemRoleController::class, 'store'])->name('保存');
        Route::delete('/role/{id}', [\app\admin\controller\system\SystemRoleController::class, 'destroy'])->name('删除');
        Route::post('/role-tree', [\app\admin\controller\system\SystemRoleController::class, 'store'])->name('保存');
    });

    /**
     * 部门
     */
    Route::group(function () {
        Route::get('/dept', [\app\admin\controller\system\SystemDeptController::class, 'index'])->name('列表');
        Route::get('/dept/{id}', [\app\admin\controller\system\SystemDeptController::class, 'show'])->name('详情');
        Route::put('/dept/{id}', [\app\admin\controller\system\SystemDeptController::class, 'update'])->name('更新');
        Route::post('/dept', [\app\admin\controller\system\SystemDeptController::class, 'store'])->name('保存');
        Route::delete('/dept/{id}', [\app\admin\controller\system\SystemDeptController::class, 'destroy'])->name('删除');
    });

    /**
     * 职位
     */
    Route::group(function () {
        Route::get('/post', [\app\admin\controller\system\SystemPostController::class, 'index'])->name('列表');
        Route::get('/post/{id}', [\app\admin\controller\system\SystemPostController::class, 'show'])->name('详情');
        Route::put('/post/{id}', [\app\admin\controller\system\SystemPostController::class, 'update'])->name('更新');
        Route::post('/post', [\app\admin\controller\system\SystemPostController::class, 'store'])->name('保存');
        Route::delete('/post/{id}', [\app\admin\controller\system\SystemPostController::class, 'destroy'])->name('删除');
    });

})->middleware([
    app\middleware\AllowCrossOriginMiddleware::class,
    app\admin\middleware\AdminAuthTokenMiddleware::class,
    app\admin\middleware\AdminAuthPermissionMiddleware::class,
    app\admin\middleware\AdminLogMiddleware::class,
    //    app\admin\middleware\RouteRestrictionMiddleware::class//演示系统拦截不允许操作路由
]);

