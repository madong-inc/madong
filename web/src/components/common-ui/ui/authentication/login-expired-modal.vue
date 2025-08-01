<script setup lang="ts">
import type {AuthenticationProps} from './types';

import {computed, watch} from 'vue';

import {useModal} from '#/components/common/core/ui-kit/popup-ui';
import {BasicAvatar} from '#/components/common/core/ui-kit/shadcn-ui';
import {Slot} from 'radix-vue';

interface Props extends AuthenticationProps {
  avatar?: string;
  zIndex?: number;
}

defineOptions({
  name: 'LoginExpiredModal',
});

const props = withDefaults(defineProps<Props>(), {
  avatar: '',
  zIndex: 0,
});

const open = defineModel<boolean>('open');

const [Modal, modalApi] = useModal();

watch(
    () => open.value,
    (val) => {
      modalApi.setState({isOpen: val});
    },
);

const getZIndex = computed(() => {
  return props.zIndex || calcZIndex();
});

/**
 * 获取最大的zIndex值
 */
function calcZIndex() {
  let maxZ = 0;
  const elements = document.querySelectorAll('*');
  [...elements].forEach((element) => {
    const style = window.getComputedStyle(element);
    const zIndex = style.getPropertyValue('z-index');
    if (zIndex && !Number.isNaN(Number.parseInt(zIndex))) {
      maxZ = Math.max(maxZ, Number.parseInt(zIndex));
    }
  });
  return maxZ + 1;
}
</script>

<template>
  <div>
    <Modal
        :closable="false"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :footer="false"
        :fullscreen-button="false"
        :header="false"
        :z-index="getZIndex"
        class="border-none px-10 py-6 text-center shadow-xl sm:w-[600px] sm:rounded-2xl md:h-[unset]"
    >
      <BasicAvatar :src="avatar" class="mx-auto mb-6 size-20"/>
      <Slot
          :show-forget-password="false"
          :show-register="false"
          :show-remember-me="false"
          :sub-title="$t('authentication.loginAgainSubTitle')"
          :title="$t('authentication.loginAgainTitle')"
      >
        <slot></slot>
      </Slot>
    </Modal>
  </div>
</template>
