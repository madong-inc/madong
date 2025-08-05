<script setup lang="ts">
import { useVModel } from '@vueuse/core'
import { Upload } from 'ant-design-vue'
import { SystemFielsApi } from '#/api/system/files/files'
import { Icon } from '#/components/icon'
import { fullUrl, stripBaseUrl } from '#/utils/'
import { computed } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  value: {
    type: String,
    default: '',
  },
  // 是否自动添加/移除公共路径前缀
  autoHandleBaseUrl: {
    type: Boolean,
    default: true
  }
})

const emits = defineEmits(['update:value', 'change'])

const api = new SystemFielsApi()
const mValue = useVModel(props, 'value', emits, {
  defaultValue: props.value,
  passive: true,
})

// 处理显示URL（确保回显时带公共路径）
const displayUrl = computed(() => {
  return props.autoHandleBaseUrl && mValue.value 
    ? fullUrl(mValue.value) 
    : mValue.value
})

// 处理存储URL（确保存储时无公共路径）
const getStorableUrl = (url: string) => {
  return props.autoHandleBaseUrl 
    ? stripBaseUrl(url) 
    : url
}

const customRequest = async (e: any) => {
  try {
    const data = await api.uploadFile({
      file: e.file,
    })
    const processedUrl = getStorableUrl(data.base_path||'')
    mValue.value = processedUrl
    emits('change', processedUrl)
    
  } catch (error) {
    console.error('Upload failed:', error)
  }
}

const handleRemove = (e: Event) => {
  e.stopPropagation()
  mValue.value = ''
  emits('change', '')
}
</script>

<template>
  <Upload
    :custom-request="customRequest"
    :show-upload-list="false"
    list-type="picture-card"
    v-bind="$attrs"
  >
    <div v-if="displayUrl" class="m-avatar-wrap">
      <Icon
        class="m-avatar-icon-delete"
        icon="ant-design:delete-outlined"
        @click="handleRemove"
      />
      <img :src="displayUrl" width="100%" />
    </div>
    <Icon v-else icon="ant-design:plus-outlined" />
  </Upload>
</template>

<style lang="less" scoped>
.m-avatar-wrap {
  position: relative;
  height: 102px;
  .m-avatar-icon-delete {
    position: absolute;
    top: 0;
    right: 0;
    cursor: pointer;
    background: rgba(0, 0, 0, 0.5);
    padding: 4px;
    border-radius: 0 0 0 8px;
    &:hover {
      color: white;
    }
  }
}
</style>
