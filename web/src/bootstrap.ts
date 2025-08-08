import {createApp, watchEffect} from 'vue';
import {registerAccessDirective} from '#/components/common/effects/access';
import {VueQueryPlugin} from '@tanstack/vue-query';
import {useTitle} from '@vueuse/core';
import {preferences} from '#/components/common/core/preferences';
import {router} from '#/router';
import {$t, setupI18n} from '#/locale';
import {initStores} from '#/components/common/stores';


// 引入epic-designer样式
import "epic-designer/dist/style.css";


import {initTippy, MotionPlugin, registerLoadingDirective} from '#/components/common-ui';

import '#/components/common/styles';

import { initComponentAdapter } from './adapter/component';
import App from './app.vue';
import { setupGlobalComponent } from './components/global';

async function bootstrap(namespace: string) {
    // 初始化组件适配器
    await initComponentAdapter();

    const app = createApp(App);

    // 全局组件
    setupGlobalComponent(app);

    // 注册v-loading指令
    // 在这里可以自定义指令名称，也可以明确提供false表示不注册这个指令
    registerLoadingDirective(app, {
        loading: 'loading',
        spinning: 'spinning',
    });

    // 配置国际化
    await setupI18n(app);

    // 配置 pinia
    await initStores(app, {namespace});

    // 注册权限指令
    registerAccessDirective(app);

    // 初始化 tippy
    initTippy(app);

    // 配置路由
    app.use(router);

    // 配置 @tanstack/vue-query
    app.use(VueQueryPlugin);

    // 配置 Motion 插件
    app.use(MotionPlugin);

    // 动态更新标题
    watchEffect(() => {
        if (preferences.app.dynamicTitle) {
            const routeTitle = router.currentRoute.value.meta?.title;
            const pageTitle = (routeTitle ? `${$t(String(routeTitle))} - ` : '') + preferences.app.name;
            useTitle(pageTitle);
        }
    });

    app.mount('#app');
}

export {bootstrap};