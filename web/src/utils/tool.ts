
import { getApiBaseUrl } from '#/api/request';
import { useSiteConfigStore } from '#/store/modules/site-config';
import type { App, Component } from 'vue';



/**
 * -转大驼峰
 * @param str
 */
export const toPascalCase = (str: any) => {
  // 将连字符或下划线替换为空格，以便后续处理
  const words = str.replaceAll(/[-_]/g, ' ').split(' ');

  // 将每个单词的首字母大写，并将其余部分保持原样
  const pascalCaseWords = words.map((word: any) => {
    if (word) {
      return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }
    return word;
  });

  // 将处理后的单词拼接成一个新的字符串
  return pascalCaseWords.join('');
};


/**
 * 转-kebab
 */
export const toKebabCase = (str: string) => {
  // 将每个单词的首字母小写，并在单词之间插入连字符
  return str
    .replaceAll(/([A-Z])/g, (match, _p1, offset) => {
      // 如果是第一个字符，直接转为小写
      if (offset === 0) {
        return match.toLowerCase();
      }
      // 否则，在大写字母前加上连字符，并将大写字母转为小写
      return `-${match.toLowerCase()}`;
    })
    .toLowerCase(); // 确保整个字符串都是小写
};


type EventShim = {
  new (...args: any[]): {
    $props: {
      onClick?: (...args: any[]) => void;
    };
  };
};

export type WithInstall<T> = T & {
  install(app: App): void;
} & EventShim;

export type CustomComponent = Component & { displayName?: string };
export const withInstall = <T extends CustomComponent>(component: T, alias?: string) => {
  (component as Record<string, unknown>).install = (app: App) => {
    const compName = component.name || component.displayName;
    if (!compName) return;
    app.component(compName, component);
    if (alias) {
      app.config.globalProperties[alias] = component;
    }
  };
  return component as WithInstall<T>;
};


/**
 * 格式化时间戳
 * @param dateTime 时间戳
 * @param fmt 格式化方式，默认：yyyy-mm-dd hh:MM:ss
 */
export const timestampFormat = (dateTime: string | number | null = null, fmt = 'yyyy-mm-dd hh:MM:ss') => {
  if (!dateTime) dateTime = Number(new Date())
  if (dateTime.toString().length === 10) {
      dateTime = +dateTime * 1000
  }

  const date = new Date(dateTime)
  let ret
  const opt: any = {
      'y+': date.getFullYear().toString(), // 年
      'm+': (date.getMonth() + 1).toString(), // 月
      'd+': date.getDate().toString(), // 日
      'h+': date.getHours().toString(), // 时
      'M+': date.getMinutes().toString(), // 分
      's+': date.getSeconds().toString(), // 秒
  }
  for (const k in opt) {
      ret = new RegExp('(' + k + ')').exec(fmt)
      if (ret) {
          fmt = fmt.replace(ret[1], ret[1].length == 1 ? opt[k] : padStart(opt[k], ret[1].length, '0'))
      }
  }
  return fmt
}

/**
 * 字符串补位
 */
const padStart = (str: string, maxLength: number, fillString = ' ') => {
  if (str.length >= maxLength) return str

  const fillLength = maxLength - str.length
  let times = Math.ceil(fillLength / fillString.length)
  while ((times >>= 1)) {
      fillString += fillString
      if (times === 1) {
          fillString += fillString
      }
  }
  return fillString.slice(0, fillLength) + str
}



/**
 * 获取资源完整地址（自动拼接CDN域名和参数）
 * @param relativeUrl 相对路径或完整URL
 * @param domain 可选自定义域名（默认使用站点CDN或当前域名）
 * @returns 完整的URL字符串
 */
export const fullUrl = (relativeUrl: string, domain: string = ''): string => {
  const siteConfig = useSiteConfigStore();

  // 1. 处理空路径直接返回域名
  if (!relativeUrl) return domain || getApiBaseUrl(null);



  // 2. 检测是否已经是完整URL或DataURL（正则合并优化）
  const isAbsoluteUrl = /^(https?:)?\/\//.test(relativeUrl);
  const isDataUrl = /^data:image\//i.test(relativeUrl);
  if (isAbsoluteUrl || isDataUrl) return relativeUrl;

  // 3. 确定最终使用的域名
  const finalDomain = domain || siteConfig.state.cdn_url || getApiBaseUrl();
  if (!finalDomain) return relativeUrl; // 保底返回原路径

  // 4. 拼接URL
  let url = `${finalDomain.replace(/\/+$/, '')}/${relativeUrl.replace(/^\/+/, '')}`;

  // 5. 添加CDN参数（仅当使用站点CDN且存在参数时）
  if (
    finalDomain === siteConfig.state.cdn_url && 
    siteConfig.state.cdn_url_params
  ) {
    const separator = url.includes('?') ? '&' : '?';
    url += `${separator}${siteConfig.state.cdn_url_params}`;
  }

  return url;
};


/**
 * 移除URL中的公共路径前缀
 * @param url 待处理的URL
 * @param domain 可选指定要移除的特定域名（默认自动检测）
 * @returns 移除前缀后的相对路径
 */
export const stripBaseUrl = (url: string, domain: string = ''): string => {
  const siteConfig = useSiteConfigStore();
  
  // 1. 处理空值
  if (!url) return '';

  // 2. 检测不需要处理的URL类型
  const isDataUrl = /^data:image\//i.test(url);
  if (isDataUrl) return url;

  // 3. 确定要移除的域名（优先级：参数指定 > CDN域名 > API域名）
  const baseDomain = domain || siteConfig.state.cdn_url || getApiBaseUrl();
  if (!baseDomain) return url;

  // 4. 标准化待移除的域名（移除尾部斜杠）
  const normalizedDomain = baseDomain.replace(/\/+$/, '');

  // 5. 移除域名部分（包括协议头可选匹配）
  let result = url
    .replace(new RegExp(`^https?:\\/\\/${normalizedDomain}\\/?`), '')
    .replace(new RegExp(`^${normalizedDomain}\\/?`), '');

  // 6. 移除可能的CDN参数（如果来自同一CDN）
  if (normalizedDomain === siteConfig.state.cdn_url && siteConfig.state.cdn_url_params) {
    const paramStr = `[?&]${siteConfig.state.cdn_url_params.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`;
    result = result.replace(new RegExp(`${paramStr}(?:&|$)`), '');
  }

  // 7. 确保不会返回空路径（至少保留原文件名）
  return result || url.split('/').pop() || '';
};



/**
 * 获取一组资源的完整地址（支持字符串或数组输入）
 * @param relativeUrls 相对路径（支持逗号分隔字符串或数组）
 * @param domain 可选自定义域名（默认使用站点CDN或当前域名）
 * @returns 完整URL数组（自动过滤空值）
 */
export const arrayFullUrl = (
  relativeUrls: string | string[],
  domain: string = ''
): string[] => {
  // 1. 统一输入为数组格式（处理字符串输入）
  const urlArray = typeof relativeUrls === 'string' 
    ? relativeUrls.split(',').filter(Boolean) 
    : [...relativeUrls];

  // 2. 使用map批量处理URL（避免直接修改原数组）
  return urlArray.map(url => fullUrl(url.trim(), domain));
};


// 可以提取为通用工具函数
export const joinUrls = (base: string, ...paths: string[]) => {
  return [base.replace(/\/+$/, ''), ...paths.map(p => p.replace(/^\/+/, ''))]
    .filter(Boolean)
    .join('/');
};