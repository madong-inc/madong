import { $t } from '#/locale';
import type { FormSchema, FormSchemaGetter } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';
import type { SystemNoticeRow } from '#/api/system/notice';

import { DictEnum } from '#/components/common/constants';
import { getPopupContainer } from '#/components/common/utils';

import { getDictOptions } from '#/utils/dict';





/**
 * 搜索栏表单
 * @returns 
 */
export function querySchemas(): FormSchema[] {
  return [
    {
      component: 'Input',
      fieldName: 'LIKE_title',
      label: $t('system.notice.list.query.title'),
      componentProps: {
        placeholder: $t('system.notice.list.query.placeholder.title')
      }
    },
    {
      component: 'Select',
      label: $t('system.notice.list.query.type'),
      componentProps: {
        getPopupContainer,
        options: getDictOptions(DictEnum.SYS_NOTICE_TYPE),
        placeholder: $t('system.notice.list.query.placeholder.type')
      },
      fieldName: 'type'
    },
  ];
}


/**
 * 定义表格字段数据
 * @param onActionClick 
 * @returns 
 */
export function useColumns(
  onActionClick: OnActionClickFn<SystemNoticeRow>,
  //@ts-ignore
  onStatusChange?: (newStatus: any, row: SystemNoticeRow) => PromiseLike<boolean | undefined>,): VxeTableGridOptions<SystemNoticeRow>['columns'] {
  return [
    { type: 'checkbox', width: 60 },
    {
      title: $t('system.notice.list.table.columns.type'),
      field: 'type',
      width: 120,
      cellRender: {
        name: 'CellTag',
        options: getDictOptions(DictEnum.SYS_NOTICE_TYPE),
      }
    },
    {

      title: $t('system.notice.list.table.columns.title'),
      align: 'left',
      field: 'title',
      minWidth: 110,
    },
    {
      title: $t('system.notice.list.table.columns.content'),
      align: 'left',
      field: 'content',
      minWidth: 200,
    },
    {
      title: $t('system.notice.list.table.columns.enabled'),
      field: 'enabled',
      width: 120,
      cellRender: {
        name: 'CellTag',
        options: getDictOptions(DictEnum.SYS_YES_NO)
      }
    },
    {
      title: $t('system.notice.list.table.columns.created_date'),
      field: 'created_date',
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellOperation', // 渲染器名称
        attrs: {
          nameField: 'title', // 自定义字段（如果需要）
          onClick: onActionClick,
        },
        options: [
          {
            code: 'edit',
            text: $t('system.notice.list.table.columns.actions.edit'),
            auth: ['admin', 'system:notice:edit'],
          },
          {
            code: 'delete',
            text: $t('system.notice.list.table.columns.actions.delete.label'),
            auth: ['admin', 'system:notice:delete'],
          },
          {
            code: 'publish',
            text: $t('system.notice.list.table.columns.actions.publish.label'),
            auth: ['admin', 'system:notice:publish'],
          }
        ],
      },
      field: 'operation',
      fixed: 'right',
      headerAlign: 'center',
      showOverflow: false,
      title: $t('system.notice.list.table.columns.operation'),
      width: 200,
    }
  ];
}




/**
 * 表单定义
 * @returns 
 */
export const formSchemas: FormSchemaGetter = () => [
  {
    component: 'Input',
    dependencies: {
      show: () => false,
      triggerFields: ['id'],
    },
    fieldName: 'id',
    label: $t('system.notice.form.modal.id'),
  },
  {
    component: 'Select',
    componentProps: {
      options: getDictOptions(DictEnum.SYS_NOTICE_TYPE),
      class: 'w-full'
    },
    fieldName: 'type',
    label: $t('system.notice.form.modal.type'),
    rules: 'required',
  },
  {
    component: 'Input',
    fieldName: 'title',
    label: $t('system.notice.form.modal.title'),
    rules: 'required',
  },
  {
    component: 'Textarea',
    componentProps: {
      width: '100%',
      rows: 6
    },
    fieldName: 'content',
    label: $t('system.notice.form.modal.content'),
    rules: 'required',
  },
  {
    component: 'RadioGroup',
    componentProps: {
      buttonStyle: 'solid',
      options: getDictOptions(DictEnum.SYS_YES_NO),
      optionType: 'button',
    },
    defaultValue:1,
    fieldName: 'enabled',
    label: $t('system.notice.form.modal.enabled'),
    rules: 'required',
    formItemClass: 'col-span-1',
  },
];
