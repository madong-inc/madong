import type { Recordable } from '#/components/common/types';

import { h } from 'vue';

import { setupVxeTable, useVxeGrid } from '#/components/common/effects/plugins/vxe-table';
import { get, isString } from '#/components/common/utils';

import { Button, Dropdown, Image, Menu, Popconfirm, Switch, Tag } from 'ant-design-vue';

import { $t } from '#/locale';

import { useForm } from './form';
import { useAccess } from '#/components/common/effects/access';
import { Icon } from '#/components/icon';


setupVxeTable({
  configVxeTable: (vxeUI) => {
    vxeUI.setConfig({
      grid: {
        align: 'center',
        border: false,
        columnConfig: {
          resizable: true,
        },

        formConfig: {
          // 全局禁用vxe-table的表单配置，使用formOptions
          enabled: false,
        },
        minHeight: 180,
        proxyConfig: {
          autoLoad: true,
          response: {
            result: 'items',
            total: 'total',
            list: '',
          },
          showActiveMsg: true,
          showResponseMsg: false,
        },
        exportConfig: {},
        round: true,
        showOverflow: true,
        size: 'small'
      },
    });

    /**
     * 解决vxeTable在热更新时可能会出错的问题
     */
    vxeUI.renderer.forEach((_item, key) => {
      if (key.startsWith('Cell')) {
        vxeUI.renderer.delete(key);
      }
    });

    // 表格配置项可以用 cellRender: { name: 'CellImage' },
    vxeUI.renderer.add('CellImage', {
      renderTableDefault(_renderOpts, params) {
        const { column, row } = params;
        return h(Image, { src: row[column.field] });
      },
    });

    // 表格配置项可以用 cellRender: { name: 'CellLink' },
    vxeUI.renderer.add('CellLink', {
      renderTableDefault(renderOpts) {
        const { props } = renderOpts;
        return h(
          Button,
          { size: 'small', type: 'link' },
          { default: () => props?.text },
        );
      },
    });

    // 单元格渲染： Tag
    vxeUI.renderer.add('CellTag', {
      renderTableDefault({ options, props }, { column, row }) {
        // 关键修改点：默认值设为空数组 
        const tagOptions = options ?? [
          { color: 'success', label: $t('common.enabled'), value: 1 },
          { color: 'error', label: $t('common.disabled'), value: 0 },
        ];
        // 安全取值逻辑 
        const value = get(row, column.field) ?? '';
        const tagItem = tagOptions.find(item => item.value === value);

        // 无有效配置时直接返回原始值 
        if (!tagOptions.length || !tagItem) {
          return h('span', { class: 'raw-value' }, String(value));
        }

        // 动态样式处理 
        return h(Tag, {
          ...props,
          color: tagItem.color || 'default',
          effect: tagItem.color ? 'light' : 'plain'
        }, tagItem.label);
      }
    });


    //开关渲染
    vxeUI.renderer.add('CellSwitch', {
      renderTableDefault({ attrs, props }, { column, row }) {
        const loadingKey = `__loading_${column.field}`;

        // 动态解析禁用状态（支持函数/布尔值）
        const resolveDisabled = () => {
          if (typeof attrs?.disabled === 'function') {
            return attrs.disabled(row);  // 传入行数据动态计算 
          }
          return attrs?.disabled ?? props?.disabled ?? false;
        };

        const finallyProps = {
          checkedChildren: $t('common.enabled'),
          checkedValue: 1,
          unCheckedChildren: $t('common.disabled'),
          unCheckedValue: 0,
          ...props,
          checked: row[column.field],
          loading: row[loadingKey] ?? false,
          disabled: resolveDisabled(), // 合并禁用状态 
          // 'onUpdate:checked': onChange,
          'onUpdate:checked': (newVal: any) => {
            if (!resolveDisabled()) { // 操作前二次验证 
              onChange(newVal);
            }
          }
        };
        async function onChange(newVal: any) {
          row[loadingKey] = true;
          try {
            const result = await attrs?.beforeChange?.(newVal, row);
            if (result !== false) {
              row[column.field] = newVal;
            }
          } finally {
            row[loadingKey] = false;
          }
        }
        return h(Switch, finallyProps);
      },
    });




    //注册操作按钮
    vxeUI.renderer.add('CellOperation', {
      renderTableDefault({ attrs, options, props }, { column, row }) {
        //基础配置
        const defaultProps = {
          size: 'small',
          type: 'link',
          class: 'operation-btn',
          ...props
        };

        let align = 'end';
        switch (column.align) {
          case 'center':
            align = 'center';
            break;
          case 'left':
            align = 'start';
            break;
          default:
            align = 'end';
        }

        // 预设配置（可扩展） 
        const presets: Record<string, Record<string, any>> = {
          delete: {
            danger: true,
            text: $t('common.delete'),
          }
        };


        // 动态解析
        const resolveDynamic = <T>(val: T | ((row: any) => T), defaultVal?: T): T => {
          try {
            //@ts-ignore
            return typeof val === 'function' ? val(row) ?? defaultVal! : val ?? defaultVal!;
          } catch {
            return defaultVal!;
          }
        };

        //权限处理
        const { hasAccessByCodes } = useAccess();
        const verifyAuth = (auth: any) => {
          if (!auth) return true;
          const authCodes = resolveDynamic(auth, []);
          return authCodes.length === 0 || hasAccessByCodes(Array.isArray(authCodes) ? authCodes : [authCodes]);
        };

        // 操作项处理
        const operations = (options || [])
          .map(opt => {
            if (isString(opt)) {
              return {
                code: opt,
                ...presets[opt],
                ...defaultProps,
                text: presets[opt]?.text || ($t(`common.${opt}`) ? $t(`common.${opt}`) : opt),
              };
            } else {
              return {
                ...defaultProps,
                ...presets[opt.code],
                ...opt,
              };
            }
          })
          .map(opt => {
            const dynamicOpt = {
              ...opt,
              disabled: resolveDynamic(opt.disabled, false) // 显式解析 
            };
            Object.keys(dynamicOpt).forEach(key => {
              if (typeof dynamicOpt[key] === 'function') {
                dynamicOpt[key] = resolveDynamic(dynamicOpt[key], []);
              }
            });

            return dynamicOpt;
          })
          .filter(opt => {
            const showState = resolveDynamic(opt.show, true);
            const authState = verifyAuth(opt.auth);
            return showState !== false && authState;
          })
          .sort((a, b) => (b.priority || 0) - (a.priority || 0));

        // 渲染子模块
        const renderBtn = (opt: any, listen = true) => {
          return h(
            Button,
            {
              ...props,
              ...{ ...opt, icon: undefined },
              danger: opt.danger,
              style: {
                display: 'flex',
                alignItems: 'center',
                gap: opt.icon ? '4px' : '0', // 根据图标存在与否调整间距
                whiteSpace: 'nowrap',
                width: '100%'
              },
              onClick: listen ? () => attrs?.onClick?.({ code: opt.code, row }) : undefined
            },
            {
              default: () => [
                opt.icon && h(Icon, { icon: opt.icon }), // 条件渲染图标 
                h('span', { class: 'align-middle' }, opt.text)
              ]
            }
          );
        };

        /**
         * 渲染删除确认
         * @param opt 
         * @returns 
         */
        const renderConfirm = (opt: any) => h(
          Popconfirm,
          {
            placement: 'top',
            title: $t('ui.actionTitle.delete', [row[attrs?.nameField || 'name']]),
            getPopupContainer: trigger =>
              trigger.closest('.vxe-table--body') || document.body,
            onConfirm: () => attrs?.onClick?.({ code: opt.code, row }),
            trigger: 'click',
            disabled: opt.disabled || false,
          },
          {
            default: () => renderBtn(opt, false), // 禁用默认点击事件 
            icon: () => h(Icon, {
              icon: 'mdi:alert-circle-outline',
              class: 'text-red-500'
            })
          }
        );

        /**
         * 
         * @param items 渲染更多按钮子模块
         * @returns 
         */
        const renderMoreMenu = (items: any) => h(
          Dropdown,
          {
            trigger: ['hover'],
            overlayClassName: 'operation-more-dropdown',
          },
          {
            default: () => h(
              Button,
              //@ts-ignore
              { ...defaultProps, icon: h(Icon, { icon: 'ant-design:more-outlined', class: 'align-middle' }) }
            ),
            overlay: () => h(
              Menu,
              {
                selectable: false,
                style: {
                  padding: '8px 0',
                  minWidth: '140px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '6px',
                }
              },
              items.map((opt: { code: string; disabled: any; }) => {
                const isDelete = opt.code === 'delete';
                return h(
                  Menu.Item,
                  {
                    key: opt.code,
                    disabled: opt.disabled,
                    style: {
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                    },
                  },
                  () => [
                    isDelete
                      ? renderConfirm(opt)
                      : renderBtn(opt),
                  ]
                );
              })
            )
          }
        );

        // 布局控制
        const MAX_VISIBLE = attrs?.maxOperations || 2;
        const visibleOps = operations.slice(0, MAX_VISIBLE);
        const hiddenOps = operations.slice(MAX_VISIBLE);

        // 最终渲染
        return h(
          'div',
          {
            class: 'cell-operation-wrapper',
            style: {
              display: 'flex',
              justifyContent: align,
              gap: '1px',
              flexWrap: 'nowrap'
            }
          },
          [
            ...visibleOps.map(opt => opt.code === 'delete' ? renderConfirm(opt) : renderBtn(opt)),
            hiddenOps.length > 0 && renderMoreMenu(hiddenOps)
          ].filter(Boolean)
        );
      }
    });




  },
  useForm,
});

export { useVxeGrid };
export type OnActionClickParams<T = Recordable<any>> = {
  code: string;
  row: T;
};
export type OnActionClickFn<T = Recordable<any>> = (
  params: OnActionClickParams<T>,
) => void;

export type * from '#/components/common/effects/plugins/vxe-table';



/**
 * vxe-table复选项选择状态
 * @param gridApi 
 * @returns bool
 */
export function isCheckboxChecked(
  gridApi: ReturnType<typeof useVxeGrid>[1],
) {
  return gridApi?.grid?.getCheckboxRecords?.()?.length > 0;
}


/**
 * vxe-table复选项选择状态-包含值返回true
 * @param gridApi 
 * @param targetKey 
 * @param excludeValues 
 * @returns 
 */
export function isCheckedInclusion<T extends Record<string, any>>(
  gridApi: ReturnType<typeof useVxeGrid>[1],
  targetKey?: keyof T | string,
  excludeValues: (string | number)[] = []
): boolean {
  const records = gridApi?.grid?.getCheckboxRecords?.() || [];
  if (records.length === 0) return false;
  console.log(records)

  // 无过滤条件时返回true
  if (!targetKey || excludeValues.length === 0) {
    return true;
  }

  // 检查所有记录是否都包含excludeValues中的至少一个值
  const allIncludeExcludedValue = records.every(record => {
    // 处理 targetKey 嵌套键
    const val = typeof targetKey === 'string'
      ? targetKey.split('.').reduce((acc, key) => acc?.[key], record)
      : record[targetKey];

    // 检查当前记录是否包含至少一个排除值
    return excludeValues.some(excl => String(val).includes(String(excl)));
  });

  return allIncludeExcludedValue;
}
