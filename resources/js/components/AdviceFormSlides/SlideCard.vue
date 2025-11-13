<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/shadcn/components/ui/button';
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';

defineProps({
    showBackward: {
        type: Boolean,
        default: true,
    },
    showForward: {
        type: Boolean,
        default: true,
    },
});

const forwardEnabled = ref(false);
const emit = defineEmits(['forward', 'backward']);

function forward() {
    emit('forward');
}

function backward() {
    emit('backward');
}

function allowForward(allow: boolean) {
    forwardEnabled.value = allow;
}
</script>

<template>
    <div style="width:100%;height;100%">
        <div class="dx-card slide-card">
            <div style="flex-grow: 1">
                <slot :allow-forward="allowForward"></slot>
            </div>
            <div style="display: flex">
                <Button variant="outline" @click="backward" v-if="showBackward">
                    <ArrowLeft class="h-4 w-4" />
                    Zurück
                </Button>
                <div style="flex-grow: 1"></div>

                <Button variant="default" @click="forward" :disabled="!forwardEnabled" v-if="showForward">
                    Weiter
                    <ArrowRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
<style #scoped>
.slide-card {
    overflow: auto;
    white-space: normal;
    height: 400px;
    margin: 20px;
    padding: 20px;
    display: flex;
    flex-direction: column;
}
</style>
