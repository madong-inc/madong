<script setup lang="ts">
import type { SupportedLanguagesType } from '#/locale';

import { SUPPORT_LANGUAGES } from '#/components/common/constants';
import { Languages } from '#/components/common/icons';
import { loadLocaleMessages } from '#/locale';
import { preferences, updatePreferences } from '#/components/common/core/preferences';

import { BasicDropdownRadioMenu, BasicIconButton } from '#/components/common/core/ui-kit/shadcn-ui';
import { SystemUserApi } from '#/api/system/user';

defineOptions({
  name: 'LanguageToggle',
});

const api= new SystemUserApi();

async function handleUpdate(value: string) {
  const locale = value as SupportedLanguagesType;
  updatePreferences({
    app: {
      locale,
    },
  });
  await loadLocaleMessages(locale);
  await api.preferences(preferences);
  window.location.reload();//刷新重新加载
}
</script>

<template>
  <div>
    <BasicDropdownRadioMenu
      :menus="SUPPORT_LANGUAGES"
      :model-value="preferences.app.locale"
      @update:model-value="handleUpdate"
    >
      <BasicIconButton>
        <Languages class="text-foreground size-4" />
      </BasicIconButton>
    </BasicDropdownRadioMenu>
  </div>
</template>
