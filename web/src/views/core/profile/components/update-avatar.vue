<script setup lang="ts">
import { computed } from 'vue';

import { useAccessStore, useAuthStore, useUserStore } from '#/components/common/stores';

import {
  Avatar,
  message,
  Upload,
  type UploadChangeParam,
} from 'ant-design-vue';

import { Icon } from '#/components/icon';
import { SystemProfileApi } from '#/api/system/profile';
import { joinUrls } from '#/utils';
import { getApiBaseUrl } from '#/api/request';

const api= new SystemProfileApi();

const emit = defineEmits(['refresh'])

defineProps({
  avatar: {
    type: String,
    default: '',
  },
});

const accessStore = useAccessStore();
const userStore = useUserStore();
const authStore = useAuthStore();

const getAction = computed(() => {
  //@ts-ignore
  return joinUrls(getApiBaseUrl(null), 'system/files/upload-image')
});

const headers = {
  Authorization: `Bearer ${accessStore.accessToken}`,
};
const handleChange = async (info: UploadChangeParam) => {
  if (info.file.status !== 'uploading') {
    console.warn(info.file, info.fileList);
  }
  if (info.file.status === 'done') {
    // 上传成功
   await  api.updateAvatar({
      avatar: info.file.response.data.base_path,
    });

    message.success('修改头像成功');
    emit('refresh')
  } else if (info.file.status === 'error') {
    message.error(`${info.file.name} file upload failed.`);
  }
};
</script>
<template>
  <Upload
    :action="getAction"
    :headers="headers"
    :max-count="1"
    :show-upload-list="false"
    accept="image/*"
    class="relative"
    @change="handleChange"
  >
    <Avatar :size="120" :src="avatar" />
    <div
      class="absolute left-[50%-120px] top-[0px] flex h-[120px] w-[120px] cursor-pointer justify-center rounded-full bg-black opacity-0 hover:opacity-30"
    >
      <Icon :size="48" icon="ant-design:cloud-upload-outlined" />
    </div>
  </Upload>
</template>
<style lang="less" scoped></style>
