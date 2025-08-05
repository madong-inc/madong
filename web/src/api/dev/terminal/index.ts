import { getApiBaseUrl, requestClient } from "#/api/request";
import { useAccessStore } from "#/components/common/stores";




export class TerminalApi {

    /**
 * 基础路径
 */
    protected baseUrl: string;

    /**
 * 构造函数
 * @param baseUrl 
 */
    constructor() {
        this.baseUrl = '/terminal';
    }


    /**
     * Web终端请求
     */
    command(commandKey: string, uuid: string, extend: string) {
        const accessStore = useAccessStore();
        const token = this.formatToken(accessStore.accessToken);
        return (
            getApiBaseUrl() + this.baseUrl + '?command=' + commandKey + '&uuid=' + uuid + '&extend=' + extend + '&token=' + token + '&server=crmd'
        )
    }

    /**
     * 配置更新
     * @param data 
     * @returns 
     */
    config(data: Partial<any>){
        return requestClient.put(`${this.baseUrl}/config`, data);
    }


    /**
     * token 格式
     * @param token 
     * @returns 
     */
    formatToken(token: null | string) {
        return token ? `Bearer ${token}` : null;
    }

}

export type * from './model';