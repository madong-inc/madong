import BaseApi from '#/api/base-api';
import { requestClient } from '#/api/request';
import { SystemFilesRow } from './files-model';


/**
 * 文件管理 API
 */
export class SystemFielsApi extends BaseApi<SystemFilesRow> {

  /**
   * 构造函数
   * @param baseUrl 
   */
  constructor() {
    super('/system/files');
  }



  /**
 * 文件上传
 * @param data
 */
  uploadFile(data: any) {
    return requestClient.upload(`${this.baseUrl}/upload-image`, data);
  }



  /**
   * 文件下载
   * @param id
   * @returns
   */
  downloadById(id: string | number) {
    return requestClient.get(`${this.baseUrl}/download-by-id/${id}`, {
      responseType: 'blob',
      isResponse: true
    } as any);
  }


}