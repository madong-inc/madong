import { $t } from '#/locale';
import type { FormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';
import type { SystemMessageRow } from '#/api/system/message';

import { getPopupContainer } from '#/components/common/utils';

import { getDictOptions } from '#/utils/dict';
import { Button } from 'ant-design-vue';
import { h } from 'vue';
import { DictEnum } from '#/components/common/constants';





/**
 * 搜索栏表单
 * @returns 
 */
export function querySchemas(): FormSchema[] {
  return [
    {
      component: 'Input',
      fieldName: 'LIKE_title',
      label: $t('system.message.list.query.title'),
      componentProps: {
        placeholder: $t('system.message.list.query.placeholder.title'),
      }
    },
    {
      component: 'Input',
      fieldName: 'LIKE_content',
      label: $t('system.message.list.query.content'),
      componentProps: {
        placeholder: $t('system.message.list.query.placeholder.content'),
      }
    },
    {
      component: 'Select',
      componentProps: {
        getPopupContainer,
        options: getDictOptions(DictEnum.SYS_MESSAGE_STATUS),
        placeholder: $t('system.message.list.query.placeholder.enabled'),
      },
      fieldName: 'status',
      label: $t('system.message.list.query.enabled'),
    },
    {
      component: 'Select',
      componentProps: {
        getPopupContainer,
        options: getDictOptions(DictEnum.SYS_MESSAGE_PRIORITY),
        placeholder: $t('system.message.list.query.placeholder.priority'),
      },
      fieldName: 'priority',
      label: $t('system.message.list.query.priority'),
    },
  ];
}


/**
 * 定义表格字段数据
 * @param onActionClick 
 * @returns 
 */
export function useColumns(
  onActionClick: OnActionClickFn<SystemMessageRow>,
  //@ts-ignore
  onStatusChange?: (newStatus: any, row: SystemMessageRow) => PromiseLike<boolean | undefined>,): VxeTableGridOptions<SystemMessageRow>['columns'] {
  return [
    { type: 'checkbox', width: 60 },
    {
      title: $t('system.message.list.table.columns.priority'),
      align: 'left',
      field: 'priority',
      width: 70,
      cellRender: {
        name: 'CellTag',
        options: getDictOptions(DictEnum.SYS_MESSAGE_PRIORITY),
      }
    },
    {
      title: $t('system.message.list.table.columns.related_id'),
      align: 'left',
      field: 'title',
      minWidth: 150,
      visible: false,
      slots: {
        //@ts-ignore
        default: ({ row }) => {
          if (row.related_id == undefined || row.related_id == '' || row.related_id == null) {
            return '/';

          }
          return h(Button, {
            type: 'link',
            onClick: (event: { stopPropagation: () => void; }) => {
              event.stopPropagation()
              onActionClick({ code: 'action', row })
            },
            style: {
              paddingLeft: '0',
              paddingRight: '0',
              fontSize: '13px'
            },
          }, row.related_id)
        }
      }
    },
    {
      title: $t('system.message.list.table.columns.title'),
      align: 'left',
      field: 'title',
      minWidth: 170,
      slots: {
        //@ts-ignore
        default: ({ row }) => {
          return h(Button, {
            type: 'link',
            onClick: (event: { stopPropagation: () => void; }) => {
              event.stopPropagation()
              onActionClick({ code: 'detail', row })
            },
            style: {
              paddingLeft: '0',
              paddingRight: '0',
              fontSize: '13px'
            },
          }, row.title)
        }
      }
    },
    {
      align: 'left',
      title: $t('system.message.list.table.columns.content'),
      field: 'content',
      minWidth: 250
    },
    {
      title: $t('system.message.list.table.columns.status'),
      field: 'status',
      width: 100,
      cellRender: {
        name: 'CellTag',
        options: getDictOptions(DictEnum.SYS_MESSAGE_STATUS)
      }
    },
    {
      title: $t('system.message.list.table.columns.type'),
      field: 'type',
      width: 100,
      visible: false,
      cellRender: {
        name: 'CellTag',
        options: getDictOptions(DictEnum.SYS_MESSAGE_TYPE)
      }
    },
    {
      width: 150,
      title: $t('system.message.list.table.columns.sender.real_name'),
      field: 'sender.real_name',
      slots: {
        //@ts-ignore
        default: ({ row }) => {
          const { sender } = row;
          return sender?.real_name || 'Administrator'
        }
      }
    },
    {
      title: $t('system.message.list.table.columns.created_date'),
      field: 'created_date',
      width: 150
    },
    {
      title: $t('system.message.list.table.columns.read_date'),
      field: 'read_date',
      width: 150,
      slots: {
        //@ts-ignore
        default: ({ row }) => {
          const { read_date } = row;
          return read_date || '/'
        }
      }
    }
  ];
}
