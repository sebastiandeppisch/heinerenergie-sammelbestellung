<script setup lang="ts">
import { reactive } from '@vue/reactivity';
import { DxPopover } from 'devextreme-vue/popover';
import DxButton from 'devextreme-vue/button';
interface Props {
  product: App.Models.Product
}
const {product} = defineProps<Props>();

const r = reactive({
    popoverVisible : false
});

function openPopover(){
    r.popoverVisible = true;
}

const popoverId = "popover-product" + product.id;

</script>
<template>
    <div class="article">
        <div class="article-name">
            {{product.name}}
        </div>
        <div class="article-detail">
            <DxButton
                icon="info"
                @click="openPopover"
                :id="popoverId" 
            />
            <DxPopover
                :width="400"
                :visible="r.popoverVisible"
                :target="'#' + popoverId"
                position="left"
            >
                <div>
                    {{product.description}}
                </div>
                <div style="margin-top:10px">
                    <div v-for="download in product.downloads">
                        <a :href="download.url" target="_blank">
                            {{download.name}}
                        </a>
                    </div>
                </div>
            </DxPopover>
        </div>
    </div>
</template>
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