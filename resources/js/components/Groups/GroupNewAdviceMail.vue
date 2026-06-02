<template>
    <div class="group-new-advice-mail pt-6">
        <div class="space-y-4">
            <div class="space-y-2">
                <Label for="new_advice_mail">E-Mail-Vorlage für neue Beratungen</Label>
                <p class="text-sm text-gray-500">Dieser Text wird als E-Mail versendet, wenn eine neue Beratung erstellt wird.</p>
            </div>
            <RichTextEditor v-model="state.value" :readonly="!canEdit" @change="onValueChanged">
                <template #toolbar-end>
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
            </RichTextEditor>
            <div v-if="form.errors.new_advice_mail" class="text-sm text-red-500">{{ form.errors.new_advice_mail }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Label } from '@/shadcn/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import { route } from 'ziggy-js';

type GroupData = App.Data.GroupData;

const props = defineProps<{
    group: GroupData;
    canEdit: boolean;
}>();

const form = useForm({
    new_advice_mail: props.group.new_advice_mail || '',
});

const state = reactive({
    value: props.group.new_advice_mail || '',
    dirty: false,
    saving: false,
});

const onValueChanged = () => {
    state.dirty = true;
};

watch(
    () => props.group.new_advice_mail,
    (newValue) => {
        state.value = newValue || '';
        state.dirty = false;
    },
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
