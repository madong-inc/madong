<script setup lang="ts">
import type {
  SplitterResizeHandleEmits,
  SplitterResizeHandleProps,
} from 'radix-vue';

import type { HTMLAttributes } from 'vue';

import { computed } from 'vue';

import { GripVertical } from '#/components/common/icons';
import { cn } from '#/components/common/utils';

import { SplitterResizeHandle, useForwardPropsEmits } from 'radix-vue';

const props = defineProps<
  SplitterResizeHandleProps & {
    class?: HTMLAttributes['class'];
    withHandle?: boolean;
  }
>();
const emits = defineEmits<SplitterResizeHandleEmits>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;
  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <SplitterResizeHandle
    v-bind="forwarded"
    :class="
      cn(
        'bg-border focus-visible:ring-ring relative flex w-px items-center justify-center after:absolute after:inset-y-0 after:left-1/2 after:w-1 after:-translate-x-1/2 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-offset-1 [&[data-orientation=vertical]>div]:rotate-90 [&[data-orientation=vertical]]:h-px [&[data-orientation=vertical]]:w-full [&[data-orientation=vertical]]:after:left-0 [&[data-orientation=vertical]]:after:h-1 [&[data-orientation=vertical]]:after:w-full [&[data-orientation=vertical]]:after:-translate-y-1/2 [&[data-orientation=vertical]]:after:translate-x-0',
        props.class,
      )
    "
  >
    <template v-if="props.withHandle">
      <div
        class="bg-border z-10 flex h-4 w-3 items-center justify-center rounded-sm border"
      >
        <GripVertical class="h-2.5 w-2.5" />
      </div>
    </template>
  </SplitterResizeHandle>
</template>
