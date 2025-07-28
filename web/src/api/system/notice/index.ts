import BaseApi from "#/api/base-api";
import { requestClient } from "#/api/request";
import type { SystemNoticeRow } from "./model";



/**
 * 抄送待阅接口
 */
export class SystemNoticeApi extends BaseApi<SystemNoticeRow> {

    constructor() {
        super('/system/notice');
    }

    /**
     * 推送公告
     * @param data 
     * @returns 
     */
    publish(data:any) {
        return requestClient.put(this.baseUrl + '/publish', data);
    }

}


export type * from './model';

