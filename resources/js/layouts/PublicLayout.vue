<script setup lang="ts">
import SmallToolbar from "../components/SmallToolbar.vue";
import BrowserTitle from "./components/BrowserTitle.vue";
import MainPublic from "./MainPublic.vue";
</script>

<template>
    <BrowserTitle />
    <MainPublic>
        <div class="outer">
            <div class="public-layout">
                <SmallToolbar />
                <div class="m-8">
                    <slot />
                </div>
            </div>
        </div>
    </MainPublic>
</template>

<style lang="scss">
.public-layout {
    max-width: 1500px;
    margin-left: auto;
    margin-right: auto;
}
.outer {
    background-color: #f2f2f2;
    width: 100%;
    height: max-content;
}
</style>
