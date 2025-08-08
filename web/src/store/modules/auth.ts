import type { Recordable, UserInfo } from '#/components/common/types';

import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { DEFAULT_HOME_PATH, LOGIN_PATH } from '#/components/common/constants';
import { resetAllStores, useAccessStore, useUserStore } from '#/components/common/stores';

import { notification, theme } from 'ant-design-vue';
import { defineStore } from 'pinia';

import { getAccessCodesApi, getUserInfoApi, loginApi, logoutApi } from '#/api';
import { $t } from '#/locale';
import { updatePreferences } from '#/components/common/core/preferences';
import { fullUrl } from '#/utils';

export const useAuthStore = defineStore('auth', () => {
  const accessStore = useAccessStore();
  const userStore = useUserStore();
  const router = useRouter();

  const loginLoading = ref(false);

  /**
   * 异步处理登录操作
   * Asynchronously handle the login process
   * @param params 登录表单数据
   */
  async function authLogin(
    params: Recordable<any>,
    onSuccess?: () => Promise<void> | void,
  ) {
    // 异步处理用户登录操作并获取 accessToken
    let userInfo: null | UserInfo = null;
    try {
      loginLoading.value = true;
      const { access_token, client_id } = await loginApi(params);
      const accessToken = access_token;
      const clientSide = client_id
      // 如果成功获取到 accessToken
      if (accessToken) {
        accessStore.setAccessToken(accessToken);

        // 获取用户信息并存储到 accessStore 中
        const [fetchUserInfoResult, accessCodes] = await Promise.all([
          fetchUserInfo(),
          getAccessCodesApi(),
        ]);

        userInfo = fetchUserInfoResult;

        userStore.setUserInfo(userInfo);
        accessStore.setAccessCodes(accessCodes);
        userStore.setAccessCodes(accessCodes);
        accessStore.setClientId(clientSide);

        if (accessStore.loginExpired) {
          accessStore.setLoginExpired(false);
        } else {
          onSuccess
            ? await onSuccess?.()
            : await router.push(userInfo.homePath || DEFAULT_HOME_PATH);
        }

        if (userInfo?.real_name) {
          notification.success({
            description: `${$t('authentication.loginSuccessDesc')}:${userInfo?.real_name}`,
            duration: 3,
            message: $t('authentication.loginSuccess'),
          });
        }
      }
    } finally {
      loginLoading.value = false;
    }

    return {
      userInfo,
    };
  }

  async function logout(redirect: boolean = true) {
    try {
      await logoutApi();
    } catch {
      // 不做任何处理
    }
    resetAllStores();
    accessStore.setLoginExpired(false);

    // 回登录页带上当前路由地址
    await router.replace({
      path: LOGIN_PATH,
      query: redirect
        ? {
          redirect: encodeURIComponent(router.currentRoute.value.fullPath),
        }
        : {},
    });
    //强制刷新重置状态
    window.location.reload();
  }


  /**
   * 偏好设置
   * @param _data 
   */
  async function setPreferences(_data: any) {
          if (!_data || typeof _data !== 'object') {
      return;
    }
    
    // 检查是否有有效的偏好设置数据
    const hasValidData = Object.keys(_data).some(key => {
      const value = _data[key];
      return value !== undefined && value !== null && 
             (typeof value === 'object' ? Object.keys(value).length > 0 : true);
    });
    
    if (!hasValidData) {
      return; // 没有有效数据，直接返回，不输出警告
    }
    const allowedKeys = ['theme', 'app']; 
    const updateData: Record<string, any> = {};
    for (const key of allowedKeys) {
      if (_data[key] !== undefined && _data[key] !== null) {
        updateData[key] = _data[key];
      }
    }
    if (Object.keys(updateData).length > 0) {
      updatePreferences(updateData);
    } else {
      console.warn('No valid preferences to update');
    }
  }

  async function fetchUserInfo(): Promise<UserInfo> {
    try {
      const userInfo = await getUserInfoApi();
      
      if (!userInfo) {
        throw new Error('Failed to fetch user info');
      }
  
      const processedUserInfo = {
        ...userInfo,
        avatar: fullUrl(userInfo.avatar || ''),
      };

      userStore.setUserInfo(processedUserInfo);
      setPreferences(userInfo.backend_setting || {});

      return processedUserInfo;
    } catch (error) {
      console.error('Error fetching user info:', error);
      throw error;
    }
  }


  async function fetchAccessCodes() {
    let accessCodes: Array<string> = [];
    accessCodes = await getAccessCodesApi();
    accessStore.setAccessCodes(accessCodes);
    return accessCodes;
  }
  function $reset() {
    loginLoading.value = false;
  }

  return {
    $reset,
    authLogin,
    fetchUserInfo,
    loginLoading,
    fetchAccessCodes,
    logout,
  };
});
