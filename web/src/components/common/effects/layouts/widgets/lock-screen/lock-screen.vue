<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

import { LockKeyhole } from '#/components/common/icons';
import { $t, useI18n } from '#/locale';
import { useLockStore } from '#/components/common/stores';
import { storeToRefs } from 'pinia';

import { useScrollLock } from '#/components/common/effects/hooks';
import { useForm, z } from '#/components/common/core/ui-kit/form-ui';
import { BasicAvatar, BasicButton } from '#/components/common/core/ui-kit/shadcn-ui';

import { useDateFormat, useNow } from '@vueuse/core';

interface Props {
  avatar?: string;
}

defineOptions({
  name: 'LockScreen',
});

withDefaults(defineProps<Props>(), {
  avatar: '',
});

defineEmits<{ toLogin: [] }>();

const { locale } = useI18n();
const lockStore = useLockStore();

const now = useNow();
const meridiem = useDateFormat(now, 'A');
const hour = useDateFormat(now, 'HH');
const minute = useDateFormat(now, 'mm');
const date = useDateFormat(now, 'YYYY-MM-DD dddd', { locales: locale.value });

const showUnlockForm = ref(false);
const { lockScreenPassword } = storeToRefs(lockStore);

const [Form, { form, validate }] = useForm(
  reactive({
    commonConfig: {
      hideLabel: true,
      hideRequiredMark: true,
    },
    schema: computed(() => [
      {
        component: 'BasicInputPassword' as const,
        componentProps: {
          placeholder: $t('ui.widgets.lockScreen.placeholder'),
        },
        fieldName: 'password',
        label: $t('authentication.password'),
        rules: z.string().min(1, { message: $t('authentication.passwordTip') }),
      },
    ]),
    showDefaultActions: false,
  }),
);

const validPass = computed(
  () => lockScreenPassword?.value === form?.values?.password,
);

async function handleSubmit() {
  const { valid } = await validate();
  if (valid) {
    if (validPass.value) {
      lockStore.unlockScreen();
    } else {
      form.setFieldError('password', $t('authentication.passwordErrorTip'));
    }
  }
}

function toggleUnlockForm() {
  showUnlockForm.value = !showUnlockForm.value;
}

useScrollLock();
</script>

<template>
  <div class="bg-background fixed z-[2000] size-full">
    <transition name="slide-left">
      <div v-show="!showUnlockForm" class="size-full">
        <div
          class="flex-col-center text-foreground/80 hover:text-foreground group my-4 cursor-pointer text-xl font-semibold"
          @click="toggleUnlockForm"
        >
          <LockKeyhole
            class="size-5 transition-all duration-300 group-hover:scale-125"
          />
          <span>{{ $t('ui.widgets.lockScreen.unlock') }}</span>
        </div>
        <div class="flex h-full justify-center px-[10%]">
          <div
            class="bg-accent flex-center relative mb-14 mr-20 h-4/5 w-2/5 flex-auto rounded-3xl text-center text-[260px]"
          >
            <span class="absolute left-4 top-4 text-xl font-semibold">
              {{ meridiem }}
            </span>
            {{ hour }}
          </div>
          <div
            class="bg-accent flex-center mb-14 h-4/5 w-2/5 flex-auto rounded-3xl text-center text-[260px]"
          >
            {{ minute }}
          </div>
        </div>
      </div>
    </transition>

    <transition name="slide-right">
      <div
        v-if="showUnlockForm"
        class="flex-center size-full"
        @keydown.enter.prevent="handleSubmit"
      >
        <div class="flex-col-center mb-10 w-[300px]">
          <BasicAvatar :src="avatar" class="enter-x mb-6 size-20" />

          <div class="enter-x mb-2 w-full items-center">
            <Form />
          </div>
          <BasicButton class="enter-x w-full" @click="handleSubmit">
            {{ $t('ui.widgets.lockScreen.entry') }}
          </BasicButton>
          <BasicButton
            class="enter-x my-2 w-full"
            variant="ghost"
            @click="$emit('toLogin')"
          >
            {{ $t('ui.widgets.lockScreen.backToLogin') }}
          </BasicButton>
          <BasicButton
            class="enter-x mr-2 w-full"
            variant="ghost"
            @click="toggleUnlockForm"
          >
            {{ $t('common.back') }}
          </BasicButton>
        </div>
      </div>
    </transition>

    <div
      class="enter-y absolute bottom-5 w-full text-center xl:text-xl 2xl:text-3xl"
    >
      <div v-if="showUnlockForm" class="enter-x mb-2 text-3xl">
        {{ hour }}:{{ minute }} <span class="text-lg">{{ meridiem }}</span>
      </div>
      <div class="text-3xl">{{ date }}</div>
    </div>
  </div>
</template>
