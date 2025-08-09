import BaseApi from '#/api/base-api';
import { SystemLoginRecordRow, SystemOperateRecordRow } from './logs-model';

/**
 * 登录日志
 */
export class SystemLoginLogApi extends BaseApi<SystemLoginRecordRow> {

  /**
   * 构造函数
   * @param baseUrl 
   */
  constructor() {
    super('/system/logs/login');
  }

  // 允许实现的接口
  protected allowedMethods: string[] = ['list', 'get', 'remove'];

}

/**
 * 操作日志
 */
export class SystemOperateLogApi extends BaseApi<SystemOperateRecordRow> {

  /**
   * 构造函数
   * @param baseUrl 
   */
  constructor() {
    super('/system/logs/operate');
  }

  // 允许实现的接口
  protected allowedMethods: string[] = ['list', 'get', 'remove'];

}