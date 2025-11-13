<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { FormControl, FormField, FormItem, FormLabel } from '@/shadcn/components/ui/form';
import { Select } from '@/shadcn/components/ui/select';
import SelectContent from '@/shadcn/components/ui/select/SelectContent.vue';
import SelectItem from '@/shadcn/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/shadcn/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/shadcn/components/ui/select/SelectValue.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

type GroupData = App.Data.GroupData;

const props = defineProps<{
    templateType: 'advice' | 'map_point';
    groups: GroupData[];
}>();

const open = defineModel<boolean>('open', { required: true });
const selectedGroupId = ref<string | undefined>(undefined);

const templateTitles = {
    advice: 'Beratungsformular erstellen',
    map_point: 'Kartenpunkt-Formular erstellen',
};

const templateDescriptions = {
    advice: 'Erstellt automatisch ein vorkonfiguriertes Beratungsformular mit allen Standard-Feldern (Vorname, Nachname, Adresse, E-Mail, Telefon, Beratungstyp).',
    map_point: 'Erstellt automatisch ein vorkonfiguriertes Kartenpunkt-Formular mit allen Standard-Feldern (Titel, Beschreibung, Koordinaten).',
};

function handleCreate() {
    if (!selectedGroupId.value) {
        toast.error('Bitte wähle eine Initiative aus.');
        return;
    }

    router.post(
        route('form-definitions.from-template'),
        {
            template_type: props.templateType,
            group_id: selectedGroupId.value,
        },
        {
            onSuccess: () => {
                toast.success('Formular wurde erfolgreich erstellt!');
                open.value = false;
            },
            onError: (errors) => {
                toast.error(`Fehler: ${Object.values(errors).join(', ')}`);
            },
        },
    );
}

function handleCancel() {
    open.value = false;
    selectedGroupId.value = undefined;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ templateTitles[templateType] }}</DialogTitle>
                <DialogDescription>
                    {{ templateDescriptions[templateType] }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <FormField v-slot="{ componentField }" name="group_id">
                    <FormItem>
                        <FormLabel>Initiative *</FormLabel>
                        <FormControl>
                            <Select v-model="selectedGroupId">
                                <SelectTrigger>
                                    <SelectValue placeholder="Wähle eine Initiative" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="group in groups" :key="group.id" :value="group.id">
                                        {{ group.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </FormControl>
                    </FormItem>
                </FormField>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleCancel">Abbrechen</Button>
                <Button @click="handleCreate">Erstellen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
