import BaseApi from '#/api/base-api';
import { SystemPostRow } from './post-model';


/**
 * 部门接口
 */
export class SystemPostApi extends BaseApi<SystemPostRow>{
  constructor() {
    super('/system/post');
  }
}