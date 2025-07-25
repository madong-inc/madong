import type {Arrayable, MaybeElementRef} from '@vueuse/core';

import type {Ref} from 'vue';
import {computed, onUnmounted, ref, unref, watch} from 'vue';

import {isFunction} from '#/components/common/utils';

import { useElementHover } from '@vueuse/core';

/**
 * 监测鼠标是否在元素内部，如果在元素内部则返回 true，否则返回 false
 * @param refElement 所有需要检测的元素。如果提供了一个数组，那么鼠标在任何一个元素内部都会返回 true
 * @param delay 延迟更新状态的时间
 * @returns 返回一个数组，第一个元素是一个 ref，表示鼠标是否在元素内部，第二个元素是一个控制器，可以通过 enable 和 disable 方法来控制监听器的启用和禁用
 */
export function useHoverToggle(
    refElement: Arrayable<MaybeElementRef>,
    delay: (() => number) | number = 500,
) {
    const isHovers: Array<Ref<boolean>> = [];
    const value = ref(false);
    const timer = ref<ReturnType<typeof setTimeout> | undefined>();
    const refs = Array.isArray(refElement) ? refElement : [refElement];
    refs.forEach((refEle) => {
        const eleRef = computed(() => {
            const ele = unref(refEle);
            return ele instanceof Element ? ele : (ele?.$el as Element);
        });
        const isHover = useElementHover(eleRef);
        isHovers.push(isHover);
    });
    const isOutsideAll = computed(() => isHovers.every((v) => !v.value));

    function setValueDelay(val: boolean) {
        timer.value && clearTimeout(timer.value);
        timer.value = setTimeout(
            () => {
                value.value = val;
                timer.value = undefined;
            },
            isFunction(delay) ? delay() : delay,
        );
    }

    const watcher = watch(
        isOutsideAll,
        (val) => {
            setValueDelay(!val);
        },
        {immediate: true},
    );

    const controller = {
        enable() {
            watcher.resume();
        },
        disable() {
            watcher.pause();
        },
    };

    onUnmounted(() => {
        timer.value && clearTimeout(timer.value);
    });

    return [value, controller] as [typeof value, typeof controller];
}
