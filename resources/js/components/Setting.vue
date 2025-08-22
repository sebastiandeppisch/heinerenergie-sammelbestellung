<script setup lang="ts">
import { reactive } from 'vue';
import { DxButton } from 'devextreme-vue/button';
import { DxHtmlEditor, DxMediaResizing, DxToolbar } from 'devextreme-vue/html-editor';
import DxNumberBox from 'devextreme-vue/number-box';
import DxSwitch from 'devextreme-vue/switch';

import axios from 'axios';
import DxTextBox from 'devextreme-vue/text-box';
import notify from 'devextreme/ui/notify';

import editorToolbar from '../htmlEditorToolbar.json';

interface Props {
    setting: App.Models.Setting;
}
const props = defineProps<Props>();

const toolbar = [...(editorToolbar as any[])];
toolbar.push({
    template: 'saveButton',
});
const state = reactive({ value: props.setting.value, dirty: false, toolbar, saving: false });

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
            setTimeout(() => {
                notify('Einstellung konnte nicht gespeichert werden', 'error', 1000);
            }, 1);
        });
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
    </div>
</template>
