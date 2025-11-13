<script setup lang="ts">
import { DxButton } from 'devextreme-vue/button';
import { DxHtmlEditor, DxMediaResizing, DxToolbar } from 'devextreme-vue/html-editor';
import DxNumberBox from 'devextreme-vue/number-box';
import DxSwitch from 'devextreme-vue/switch';
import { computed, reactive, ref } from 'vue';

import axios from 'axios';
import DxTextBox from 'devextreme-vue/text-box';
import notify from 'devextreme/ui/notify';
import { Button } from '@/shadcn/components/ui/button';
import { Upload } from 'lucide-vue-next';

import editorToolbar from '../htmlEditorToolbar.json';

interface Props {
    setting: App.Models.Setting;
}
const props = defineProps<Props>();

const toolbar = [...(editorToolbar as any[])];
toolbar.push({
    template: 'saveButton',
});
const state = reactive({ value: props.setting.value, dirty: false, toolbar, saving: false, uploading: false });
const fileInput = ref<HTMLInputElement>();

const onValueChanged = (e: { value: string }) => {
    state.dirty = true;
};

const save = () => {
    state.saving = true;
    axios
        .put('/api/settings/' + props.setting.id, {
            value: state.value,
            key: props.setting.key,
        })
        .then((response) => {
            state.saving = false;
            state.dirty = false;
            //timeout 100ms to make sure the value is updated
            setTimeout(() => {
                notify('Einstellung gespeichert', 'success', 1000);
            }, 1);
        })
        .catch((error) => {
            state.saving = false;
            setTimeout(() => {
                notify('Einstellung konnte nicht gespeichert werden', 'error', 1000);
            }, 1);
        });
};

const imageUrl = computed(() => {
    if (!state.value) return null;
    // If it's already a full URL, return it
    if (state.value.startsWith('http://') || state.value.startsWith('https://')) {
        return state.value;
    }
    // Otherwise, treat it as a relative path
    return state.value.startsWith('/') ? state.value : '/' + state.value;
});

const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        await uploadFile(file);
    }
};

const uploadFile = async (file: File) => {
    state.uploading = true;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('path', 'logos/');

    try {
        const response = await axios.post('/api/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        // Save the relative path
        state.value = response.data.url;
        state.dirty = true;
        await save();
        setTimeout(() => {
            notify('Bild hochgeladen', 'success', 1000);
        }, 1);
    } catch (error) {
        setTimeout(() => {
            notify('Bild konnte nicht hochgeladen werden', 'error', 1000);
        }, 1);
    } finally {
        state.uploading = false;
        // Reset file input
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div>
        <div v-if="props.setting.type === 'text'">
            <DxHtmlEditor v-model:value="state.value" :on-value-changed="onValueChanged" :allow-soft-line-break="true">
                <DxMediaResizing :enabled="true" />
                <DxToolbar :multiline="true" :items="toolbar" />
                <template #saveButton>
                    <DxButton type="default" @click="save" :disabled="!state.dirty" icon="save" />
                </template>
            </DxHtmlEditor>
        </div>
        <div v-else-if="props.setting.type === 'integer'">
            <DxNumberBox v-model:value="state.value" :on-value-changed="save" />
        </div>
        <div v-else-if="props.setting.type === 'boolean'">
            <DxSwitch v-model:value="state.value" :on-value-changed="save" />
        </div>
        <div v-else-if="props.setting.type === 'string'">
            <DxTextBox v-model:value="state.value" :on-value-changed="save" />
        </div>
        <div v-else-if="props.setting.type === 'image'" class="space-y-4">
            <!-- Current Image Display -->
            <div v-if="imageUrl" class="relative inline-block">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img :src="imageUrl" :alt="props.setting.name" class="max-h-32 max-w-32 rounded-lg border object-contain" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <p class="text-sm text-gray-600">Aktuelles Bild</p>
                        <p class="text-xs text-gray-500">{{ state.value }}</p>
                    </div>
                </div>
            </div>

            <!-- File Input -->
            <input ref="fileInput" type="file" accept="image/*" @change="handleFileSelect" class="hidden" />

            <Button type="button" variant="outline" @click="triggerFileInput" :disabled="state.uploading || state.saving" class="w-full">
                <Upload class="mr-2 h-4 w-4" />
                {{ state.uploading ? 'Wird hochgeladen...' : imageUrl ? 'Bild ersetzen' : 'Bild hochladen' }}
            </Button>
        </div>
    </div>
</template>
