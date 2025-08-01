<script setup lang="ts">
import { computed, nextTick, ref } from "vue";

import { useDrawer, Tree } from "#/components/common-ui";
import { $t } from "#/locale";
import { cloneDeep } from "#/components/common/utils";

import { useForm } from "#/adapter/form";

import { omit } from "lodash-es";
import { Spin, Tag, Button,Alert } from "ant-design-vue";

import { grantMenuFormSchemas } from "../data";

import { DataNode } from "ant-design-vue/es/tree";
import { Recordable } from "#/components/common/types";
import { getDictOptions } from "#/utils";
import { DictEnum } from "#/components/common/constants";
import { Options } from "sortablejs";
import { TenantSubscriptionApi, TenantSubscriptionRow } from "#/api/platform/tenant-subscription";
import { SystemAuthApi } from "#/api/system/auth";




const emit = defineEmits<{ reload: [] }>();

const api = new TenantSubscriptionApi();
const authApi = new SystemAuthApi();

const formData = ref<TenantSubscriptionRow>();
const isUpdate = ref<Boolean>(false);
const permissions = ref<DataNode[]>([]);
const loadingPermissions = ref(false);



const [Form, formApi] = useForm({
  commonConfig: {
    formItemClass: "col-span-2",
    componentProps: {
      class: "w-full",
    },
    labelWidth: 110,
  },
  schema: grantMenuFormSchemas(),
  showDefaultActions: false,
  wrapperClass: "grid-cols-2",
});


const [Modal, drawerApi] = useDrawer({
  async onOpenChange(isOpen) {
    if (isOpen) {
      isUpdate.value = false;
      formApi.resetForm();
      const { record } = drawerApi.getData<Record<string, any>>();
      //编辑
      if (record?.id) {
        isUpdate.value = true;
        const permissions = await api.packagePermissionIds({id:record.id});
        formData.value = await api.get(record.id);
        //@ts-nocheck
        formData.value.permissions= permissions||[];
        await formApi.setValues(formData.value);
      }

      if (permissions.value.length === 0) {
        loadPermissions();
      }
    }
  },
  onConfirm: handleConfirm,
});

function filterPermissions(permissions: (string | null | undefined)[]): string[] {
  return permissions.filter((permission): permission is string => permission != null);
}

async function loadPermissions() {
  loadingPermissions.value = true;
  try {
    const res = await authApi.getUserPermission();
    permissions.value = (res as unknown) as DataNode[];
  } finally {
    loadingPermissions.value = false;
  }
}

/**
 * 表单提交
 */
async function handleConfirm() {
  try {
   const { valid } = await formApi.validate();
    if (!valid) return;
    const values = await formApi.getValues();
    drawerApi.lock();
    const filteredData = {
      ...values,
      permissions: filterPermissions(values.permissions),
    };

     api.grantPermission({ ...filteredData, id: formData.value.id })
      .then(() => {
        emit("reload");
        drawerApi.close();
      })
      .catch(() => {
        drawerApi.unlock();
      });
  } catch (error) {
    console.error(error);
  } finally {
    drawerApi.lock(false);
  }
}

const title = computed(() => {
  return $t("platform.tenant_subscription.form.grant_menu_title");
});

function getNodeClass(node: Recordable<any>) {
  const classes: string[] = [];
  if (node.value?.type === 4) {
    classes.push("inline-flex");
    if (node.index % 3 >= 1) {
      classes.push("!pl-0");
    }
  }

  return classes.join(" ");
}


</script>

<template>
  <Modal :title="title" class="w-[800px]">
    <Alert
      message="授权后管理员拥有授权的权限"
      type="warning"
      class="ml-10 mb-5"
      closable
    />
    <Form>
      <template #permissions="slotProps">
        <Spin :spinning="loadingPermissions" wrapper-class-name="w-full">
          <Tree
            ref="treeRef"
            :tree-data="permissions"
            multiple
            bordered
            :default-expanded-level="10"
            :get-node-class="getNodeClass"
            v-bind="slotProps"
            value-field="id"
            label-field="title"
            icon-field="icon"
          >
            <template #node="{ value }">
              <Tag
                v-for="item in getDictOptions(DictEnum.SYS_MENU_TYPE)"
                :key="item.value"
                v-show="item.value === value.type" 
                :color="item?.color||'default'"
                class="dynamic-tag"
              >
                {{ item.label }}
              </Tag>
              {{ $t(value.title) }}
            </template>
          </Tree>
        </Spin>
      </template>
    </Form>
  </Modal>
</template>

<style lang="css" scoped>
:deep(.ant-tree-title) {
  .tree-actions {
    display: none;
    margin-left: 20px;
  }
}

:deep(.ant-tree-title:hover) {
  .tree-actions {
    display: flex;
    flex: auto;
    justify-content: flex-end;
    margin-left: 20px;
  }
}
</style>
