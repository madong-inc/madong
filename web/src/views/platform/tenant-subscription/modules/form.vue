<script setup lang="ts">
import { computed, nextTick, ref } from "vue";

import { useDrawer, Tree } from "#/components/common-ui";
import { $t } from "#/locale";
import { cloneDeep } from "#/components/common/utils";

import { useForm } from "#/adapter/form";

import { omit } from "lodash-es";
import { Spin, Tag, Button } from "ant-design-vue";

import { formSchemas } from "../data";

import type { SystemMenuRow } from "#/api/system/menu";
import { SystemMenuApi } from "#/api/system/menu";
import { DataNode } from "ant-design-vue/es/tree";
import { Recordable } from "#/components/common/types";
import { getDictOptions } from "#/utils";
import { DictEnum } from "#/components/common/constants";
import { Options } from "sortablejs";
import { TenantSubscriptionApi, TenantSubscriptionRow } from "#/api/platform/tenant-subscription";



const emit = defineEmits<{ reload: [] }>();

const api = new TenantSubscriptionApi();
const menuApi = new SystemMenuApi();

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
  schema: formSchemas(),
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
        formData.value = await api.get(record.id);
        await formApi.setValues(formData.value);
      }
    }
  },
  onConfirm: handleConfirm,
});




/**
 * 表单提交
 */
async function handleConfirm() {
  try {
   const { valid } = await formApi.validate();
    if (!valid) return;
    const values = await formApi.getValues();
    drawerApi.lock();
    (isUpdate.value ? api.update({ id: formData.value.id,...values }) : api.create(omit(values)))
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
  return isUpdate.value ? $t("common.edit") : $t("common.create");
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
