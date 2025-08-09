import type { HttpResponse, RequestClientOptions } from '#/components/common/effects/request';

import { preferences } from '#/components/common/core/preferences';
import {
  authenticateResponseInterceptor,
  errorMessageResponseInterceptor,
  RequestClient,
} from '#/components/common/effects/request';
import { useAccessStore, useAuthStore } from '#/components/common/stores';

import { message } from 'ant-design-vue';

// import { useAuthStore } from '#/store';

import { refreshTokenApi } from './core';



const clientKey = import.meta.env.VITE_GLOB_CLIENT_KEY;

function createRequestClient(baseURL: string, options?: RequestClientOptions) {
  const client = new RequestClient({
    ...options,
    baseURL,
  });


  /**
   * 重新认证逻辑
   */
  async function doReAuthenticate() {
    console.warn('Access token or refresh token is invalid or expired. ');
    const accessStore = useAccessStore();
    const authStore = useAuthStore();
    accessStore.setAccessToken(null);
    if (
      preferences.app.loginExpiredMode === 'modal' &&
      accessStore.accessChecked
    ) {
      accessStore.setLoginExpired(true);
    } else {
      await authStore.logout();
    }
  }


  /**
   * 刷新token逻辑
   */
  async function doRefreshToken() {
    const accessStore = useAccessStore();
    const resp = await refreshTokenApi();
    const newToken = resp.data;
    accessStore.setAccessToken(newToken);
    return newToken;
  }

  /**
   * token 格式
   * @param token 
   * @returns 
   */
  function formatToken(token: null | string) {
    return token ? `Bearer ${token}` : null;
  }

  function formatClientId(clientId: null | string | undefined) {
    return clientId ? clientId : null;
  }

  // 请求头处理
  client.addRequestInterceptor({
    fulfilled: async (config) => {
      const accessStore = useAccessStore();
      config.headers.Authorization = formatToken(accessStore.accessToken);
      config.headers['Accept-Language'] = preferences.app.locale;
      config.headers[clientKey] = formatClientId(accessStore.clientId);
      return config;
    },
  });

  // response数据解构
  client.addResponseInterceptor<HttpResponse>({
    fulfilled: (response) => {
      const { data: responseData, status } = response;
      const { code, data } = responseData;

      //@ts-ignore
      const { isResponse } = response.config;
      if (isResponse !== undefined && isResponse == true) {
        return response.data;
      }

      // 变更
      if (status >= 200 && status < 400 && code === 0) {
        return data;
      } else if (status >= 200 && status < 400 && code !== 0) {
        return Promise.reject(response);
      }

      throw Object.assign({}, response, { response });
    },
  });


  // token过期的处理
  client.addResponseInterceptor(
    authenticateResponseInterceptor({
      client,
      doReAuthenticate,
      doRefreshToken,
      enableRefreshToken: preferences.app.enableRefreshToken,
      formatToken,
    }),
  );

  // 通用的错误处理,如果没有进入上面的错误处理逻辑，就会进入这里
  client.addResponseInterceptor(

    errorMessageResponseInterceptor((msg: string, _error) => {
      // 这里可以根据业务进行定制,你可以拿到 error 内的信息进行定制化处理，根据不同的 code 做不同的提示，而不是直接使用 message.error 提示 msg
      // 当前mock接口返回的错误字段是 error 或者 message


      // 变更
      if (_error?.data?.code === 0) {
        message.error(msg);
      } else {
        message.error(_error?.data?.msg);
      }
    }),
  );

  return client;
}


export const getApiBaseUrl = (customValue?: string | null): string => {
  if (customValue) {
    return customValue === 'useCurrentDomain' 
        ? window.location.protocol + '//' + window.location.host 
        : customValue;
  }

  const envValue = import.meta.env.VITE_GLOB_API_URL;
  if (envValue) {
    return envValue === 'useCurrentDomain' 
        ? window.location.protocol + '//' + window.location.host 
        : envValue;
  }
  return window.location.protocol + '//' + window.location.host;
}


export const requestClient = createRequestClient(getApiBaseUrl(null), {
  responseReturn: 'data',
});


export const baseRequestClient = new RequestClient({ baseURL: getApiBaseUrl(null) });

