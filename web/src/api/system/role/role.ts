import BaseApi from '#/api/base-api';
import { requestClient } from '#/api/request';
import type { SystemRoleRow } from './role-model';


/**
 * 路由管理接口
 */
export class SystemRoleApi extends BaseApi<SystemRoleRow> {


  /**
   * 构造函数
   * @param baseUrl 
   */
  constructor() {
    super('/system/role');
  }

    /**
     * 分配数据权限
     * @param data 
     * @returns 
     */
    dataScope(data: Record<string, any>) : Promise<SystemRoleRow> {
      return requestClient.put<any>(`${this.baseUrl}/data-scope`, data);
    }

}





