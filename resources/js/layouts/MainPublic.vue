<script setup lang="ts">
import { Toaster } from '@/shadcn/components/ui/sonner';
import { computed, onMounted, reactive } from 'vue';
import 'vue-sonner/style.css';
import { getScreenSizeInfo } from '../utils/media-query';
import AppFooter from './AppFooter.vue';

const screen = reactive({ getScreenSizeInfo: {} as { isXSmall: boolean; isLarge: boolean; cssClasses: string[] } });
screen.getScreenSizeInfo = getScreenSizeInfo();

onMounted(() => {
    window.addEventListener('resize', () => {
        screen.getScreenSizeInfo = getScreenSizeInfo();
    });
});

const cssClasses = computed(() => {
    return ['app'].concat(screen.getScreenSizeInfo.cssClasses);
});
</script>

<template>
    <div id="root">
        <div :class="cssClasses" class="flex h-full w-full bg-muted">
            <div class="layout">
                <div class="content">
                    <slot></slot>
                </div>
                <AppFooter />
            </div>
        </div>
        <Toaster :richColors="true" position="top-center" />
    </div>
</template>

<style lang="scss">
html,
body {
    margin: 0px;
    min-height: 100%;
    height: 100%;
}

#root {
    height: 100%;
}

* {
    box-sizing: border-box;
}

.layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-width: 100vw;
}

.content {
    flex-grow: 1;
    flex-direction: column;
}
</style>
