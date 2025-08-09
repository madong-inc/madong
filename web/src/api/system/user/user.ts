import BaseApi from '#/api/base-api';
import { requestClient } from '#/api/request';
import { User } from './user-model';



export class SystemUserApi extends BaseApi<User> {

  constructor() {
    super('/system/user');
  }


  /**
 *
 * @param data 重置密码
 * @returns
 */
  resetPassword(data: Record<string, any>): Promise<User[]> {
    return requestClient.put(this.baseUrl + '/reset-password', data);
  }

  /**
   * 扮演用户
   * @param data 参数
   */
  playUser(data: Record<string, any>) {
    return requestClient.put(this.baseUrl + '/play-user', data);
  }

  /**
   * 退出扮演
   * @param data 参数
   */
  unPlayUser(data: any) {
    return requestClient.put(this.baseUrl + '/unplay-user', data);
  }

  /**
   * 授权角色
   * @param data 参数
   */
  grantRole(data: any) {
    return requestClient.put(this.baseUrl + '/grant-role', data);
  }

  /**
   * 锁定用户
   * @param data 参数
   */
  locked(data: any) {
    return requestClient.put(this.baseUrl + '/locked', data);
  }

  /**
   * 取消锁定用户
   * @param data 参数
   */
  unLocked(data: any) {
    return requestClient.put(this.baseUrl + '/un-locked', data);
  }


  /**
   * 保存用户偏好设置
   * @param data 
   * @returns 
   */
  preferences(data:any){
    return requestClient.put(this.baseUrl + '/preferences', data);
  }

}




