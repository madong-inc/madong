<script setup lang="ts">
import type { Recordable } from '#/components/common/core/typings';

import type { BasicFormSchema } from '#/components/common/core/ui-kit/form-ui';

import { computed, reactive } from 'vue';
import { useRouter } from 'vue-router';

import { $t } from '#/locale';

import { useForm } from '#/components/common/core/ui-kit/form-ui';
import { BasicButton } from '#/components/common/core/ui-kit/shadcn-ui';

import Title from './auth-title.vue';

interface Props {
  formSchema: BasicFormSchema[];
  /**
   * @zh_CN 是否处于加载处理状态
   */
  loading?: boolean;
  /**
   * @zh_CN 登录路径
   */
  loginPath?: string;
  /**
   * @zh_CN 标题
   */
  title?: string;
  /**
   * @zh_CN 描述
   */
  subTitle?: string;
  /**
   * @zh_CN 按钮文本
   */
  submitButtonText?: string;
}

defineOptions({
  name: 'AuthenticationCodeLogin',
});

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  loginPath: '/auth/login',
  submitButtonText: '',
  subTitle: '',
  title: '',
});

const emit = defineEmits<{
  submit: [Recordable<any>];
}>();

const router = useRouter();

const [Form, formApi] = useForm(
    reactive({
      commonConfig: {
        hideLabel: true,
        hideRequiredMark: true,
      },
      schema: computed(() => props.formSchema),
      showDefaultActions: false,
    }),
);

async function handleSubmit() {
  const { valid } = await formApi.validate();
  const values = await formApi.getValues();
  if (valid) {
    emit('submit', values);
  }
}

function goToLogin() {
  router.push(props.loginPath);
}

defineExpose({
  getFormApi: () => formApi,
});
</script>

<template>
  <div>
    <Title>
      <slot name="title">
        {{ title || $t('authentication.welcomeBack') }} 📲
      </slot>
      <template #desc>
        <span class="text-muted-foreground">
          <slot name="subTitle">
            {{ subTitle || $t('authentication.codeSubtitle') }}
          </slot>
        </span>
      </template>
    </Title>
    <Form />
    <BasicButton
        :class="{
        'cursor-wait': loading,
      }"
        :loading="loading"
        class="w-full"
        @click="handleSubmit"
    >
      <slot name="submitButtonText">
        {{ submitButtonText || $t('common.login') }}
      </slot>
    </BasicButton>
    <BasicButton class="mt-4 w-full" variant="outline" @click="goToLogin()">
      {{ $t('common.back') }}
    </BasicButton>
  </div>
</template>