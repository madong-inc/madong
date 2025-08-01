<script lang="ts" setup>
import type { Component } from 'vue';

import type { AlertProps } from './alert';

import { computed, h, nextTick, ref } from 'vue';

import { useSimpleLocale } from '#/components/common/effects/hooks';
import {
  CircleAlert,
  CircleCheckBig,
  CircleHelp,
  CircleX,
  Info,
  X,
} from '#/components/common/icons';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogTitle,
  BasicButton,
  BasicLoading,
  BasicRenderContent,
} from '#/components/common/core/ui-kit/shadcn-ui';
import { globalShareState, cn } from '#/components/common/utils';

import { provideAlertContext } from './alert';

const props = withDefaults(defineProps<AlertProps>(), {
  bordered: true,
  buttonAlign: 'end',
  centered: true,
  containerClass: 'w-[520px]',
});
const emits = defineEmits(['closed', 'confirm', 'opened']);
const open = defineModel<boolean>('open', { default: false });
const { $t } = useSimpleLocale();
const components = globalShareState.getComponents();
const isConfirm = ref(false);

function onAlertClosed() {
  emits('closed', isConfirm.value);
  isConfirm.value = false;
}

const getIconRender = computed(() => {
  let iconRender: Component | null = null;
  if (props.icon) {
    if (typeof props.icon === 'string') {
      switch (props.icon) {
        case 'error': {
          iconRender = h(CircleX, {
            style: { color: 'hsl(var(--destructive))' },
          });
          break;
        }
        case 'info': {
          iconRender = h(Info, { style: { color: 'hsl(var(--info))' } });
          break;
        }
        case 'question': {
          iconRender = CircleHelp;
          break;
        }
        case 'success': {
          iconRender = h(CircleCheckBig, {
            style: { color: 'hsl(var(--success))' },
          });
          break;
        }
        case 'warning': {
          iconRender = h(CircleAlert, {
            style: { color: 'hsl(var(--warning))' },
          });
          break;
        }
        default: {
          iconRender = null;
          break;
        }
      }
    }
  } else {
    iconRender = props.icon ?? null;
  }
  return iconRender;
});

function doCancel() {
  isConfirm.value = false;
  handleOpenChange(false);
}

function doConfirm() {
  isConfirm.value = true;
  handleOpenChange(false);
  emits('confirm');
}

provideAlertContext({
  doCancel,
  doConfirm,
});

function handleConfirm() {
  isConfirm.value = true;
  emits('confirm');
}

function handleCancel() {
  isConfirm.value = false;
}

const loading = ref(false);
async function handleOpenChange(val: boolean) {
  await nextTick();
  if (!val && props.beforeClose) {
    loading.value = true;
    try {
      const res = await props.beforeClose({ isConfirm: isConfirm.value });
      if (res !== false) {
        open.value = false;
      }
    } finally {
      loading.value = false;
    }
  } else {
    open.value = val;
  }
}
</script>
<template>
  <AlertDialog :open="open" @update:open="handleOpenChange">
    <AlertDialogContent
      :open="open"
      :centered="centered"
      :overlay-blur="overlayBlur"
      @opened="emits('opened')"
      @closed="onAlertClosed"
      :class="
        cn(
          containerClass,
          'left-0 right-0 mx-auto flex max-h-[80%] flex-col p-0 duration-300 sm:rounded-[var(--radius)] md:w-[520px] md:max-w-[80%]',
          {
            'border-border border': bordered,
            'shadow-3xl': !bordered,
          },
        )
      "
    >
      <div :class="cn('relative flex-1 overflow-y-auto p-3', contentClass)">
        <AlertDialogTitle v-if="title">
          <div class="flex items-center">
            <component :is="getIconRender" class="mr-2" />
            <span class="flex-auto">{{ $t(title) }}</span>
            <AlertDialogCancel v-if="showCancel" as-child>
              <BasicButton
                variant="ghost"
                size="icon"
                class="rounded-full"
                :disabled="loading"
                @click="handleCancel"
              >
                <X class="text-muted-foreground size-4" />
              </BasicButton>
            </AlertDialogCancel>
          </div>
        </AlertDialogTitle>
        <AlertDialogDescription>
          <div class="m-4 min-h-[30px]">
            <BasicRenderContent :content="content" render-br />
          </div>
          <BasicLoading v-if="loading && contentMasking" :spinning="loading" />
        </AlertDialogDescription>
        <div
            class="flex items-center justify-end gap-x-2"
            :class="`justify-${buttonAlign}`"
        >
          <BasicRenderContent :content="footer" />
          <AlertDialogCancel v-if="showCancel"  as-child>
            <component
              :is="components.DefaultButton || BasicButton"
              :disabled="loading"
              variant="ghost"
              @click="handleCancel"
            >
              {{ cancelText || $t('cancel') }}
            </component>
          </AlertDialogCancel>
          <AlertDialogAction as-child>
            <component
              :is="components.PrimaryButton || BasicButton"
              :loading="loading"
              @click="handleConfirm"
            >
              {{ confirmText || $t('confirm') }}
            </component>
          </AlertDialogAction>
        </div>
      </div>
    </AlertDialogContent>
  </AlertDialog>
</template>
