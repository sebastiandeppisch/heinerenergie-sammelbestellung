<template>
    <div class="group-new-advice-mail pt-6">
        <div class="space-y-4">
            <div class="space-y-2">
                <Label for="new_advice_mail">E-Mail-Vorlage für neue Beratungen</Label>
                <p class="text-sm text-gray-500">
                    Dieser Text wird als E-Mail versendet, wenn eine neue Beratung erstellt wird.
                </p>
            </div>
            <DxHtmlEditor
                v-model:value="state.value"
                :on-value-changed="onValueChanged"
                :allow-soft-line-break="true"
                :read-only="!canEdit"
            >
                <DxMediaResizing :enabled="true" />
                <DxToolbar :multiline="true" :items="toolbar" />
                <template #saveButton>
                    <Button
                        type="button"
                        :variant="state.dirty ? 'default' : 'outline'"
                        @click="save"
                        :disabled="!state.dirty || state.saving"
                        :size="state.dirty ? 'default' : 'icon'"
                    >
                        <Save class="h-4 w-4" />
                        <span v-if="state.dirty">Speichern</span>
                    </Button>
                </template>
            </DxHtmlEditor>
            <div v-if="form.errors.new_advice_mail" class="text-sm text-red-500">{{ form.errors.new_advice_mail }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { DxHtmlEditor, DxMediaResizing, DxToolbar } from 'devextreme-vue/html-editor';
import { reactive, watch } from 'vue';
import { Button } from '@/shadcn/components/ui/button';
import { Label } from '@/shadcn/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { route } from 'ziggy-js';

import editorToolbar from '../../htmlEditorToolbar.json';

type GroupData = App.Data.GroupData;

const props = defineProps<{
    group: GroupData;
    canEdit: boolean;
}>();

const form = useForm({
    new_advice_mail: props.group.new_advice_mail || '',
});

const toolbar = [...(editorToolbar as any[])];
toolbar.push({
    template: 'saveButton',
});

const state = reactive({
    value: props.group.new_advice_mail || '',
    dirty: false,
    saving: false,
    toolbar,
});

const onValueChanged = (e: { value: string }) => {
    state.dirty = true;
    state.value = e.value;
};

// Update state when group changes
watch(
    () => props.group.new_advice_mail,
    (newValue) => {
        state.value = newValue || '';
        state.dirty = false;
    }
);

const save = () => {
    if (!state.dirty) return;

    state.saving = true;
    form.new_advice_mail = state.value;
    form.put(route('groups.new-advice-mail.update', props.group.id), {
        preserveScroll: true,
        onSuccess: () => {
            state.dirty = false;
            state.saving = false;
        },
        onError: () => {
            state.saving = false;
        },
    });
};
</script>

