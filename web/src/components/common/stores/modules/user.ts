import { acceptHMRUpdate, defineStore } from 'pinia';

import type { BasicUserInfo } from '#/components/common/core/typings';

// interface BasicUserInfo {
//     [key: string]: any;
//     /**
//      * 头像
//      */
//     avatar: string;
//     /**
//      * 用户昵称
//      */
//     realName: string;
//     /**
//      * 用户角色
//      */
//     roles?: string[];
//     /**
//      * 用户id
//      */
//     userId: string;
//     /**
//      * 用户名
//      */
//     username: string;

// }

interface AccessState {
    /**
     * 权限码
     */
    accessCodes: string[];

    /**
     * 用户信息
     */
    userInfo: BasicUserInfo | null;
    /**
     * 用户角色
     */
    userRoles: string[];
}

/**
 * @zh_CN 用户信息相关
 */
export const useUserStore = defineStore('core-user', {
    actions: {
        setAccessCodes(accessCodes: string[]) {
            this.accessCodes = accessCodes;
        },


        setUserInfo(userInfo: BasicUserInfo | null) {
            // 设置用户信息
            this.userInfo = userInfo;
            // 设置角色信息
            const roles = userInfo?.roles ?? [];
            this.setUserRoles(roles);
        },
        setUserRoles(roles: string[]) {
            this.userRoles = roles;
        },
    },
    state: (): AccessState => ({
        accessCodes: [],
        userInfo: null,
        userRoles: [],
    }),
});

// 解决热更新问题
const hot = import.meta.hot;
if (hot) {
    hot.accept(acceptHMRUpdate(useUserStore, hot));
}