<template>
    <div class="article">
        <div class="article-name">
            {{product.name}}
        </div>
        <div class="article-detail">
            <i class="dx-icon-info" v-if="product.description !== null" @mouseenter="togglePopover" @mouseleave="togglePopover" :id="popoverId" style="cursor:pointer;"></i>
            &nbsp;
            <a :href="product.url" v-if="product.url !== null" target="_blank">
                <i class="dx-icon-link"></i>
            </a>
            <DxPopover
                :width="300"
                :visible="r.popoverVisible"
                :target="'#' + popoverId"
                position="top"
            >
                {{product.description}}
            </DxPopover>
        </div>
    </div>
</template>
<script setup lang="ts">
import { reactive } from '@vue/reactivity';
import { DxPopover } from 'devextreme-vue/popover';

interface Props {
  product: App.Models.Product
}
const {product} = defineProps<Props>();

const r = reactive({
    popoverVisible : false
});

function togglePopover(){
    r.popoverVisible = !r.popoverVisible;
}

const popoverId = "popover-product" + product.id;

</script>
<style scoped>
.article{
    display: flex;
    flex-direction: row;
    /*width: 100px;*/
    /*max-width: 40vw;*/
}
.article-name{
    width: 100%;
}
.article-detail{
    flex: 1;
}
</style>