<script setup lang="ts">
import ChecklistEntry from '@/components/ChecklistEntry.vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/shadcn/components/ui/accordion';
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

type ChecklistEntryData = App.Data.ChecklistEntryData;
type FormDefinitionData = App.Data.FormDefinitionData;

const props = defineProps<{
    checklistEntries: ChecklistEntryData[];
    availableChecklists: FormDefinitionData[];
    adviceId: string;
}>();

const dialogOpen = ref(false);
const adding = ref(false);

function addChecklist(formDefinitionId: string) {
    adding.value = true;
    router.post(
        route('checklist-entries.store', { advice: props.adviceId }),
        { form_definition_id: formDefinitionId },
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
            onFinish: () => {
                adding.value = false;
            },
        },
    );
}
</script>

<template>
    <div>
        <Accordion v-if="checklistEntries.length > 0" type="multiple" class="w-full">
            <AccordionItem v-for="entry in checklistEntries" :key="entry.id" :value="entry.id">
                <AccordionTrigger
                    ><div style="font-size: 1.2rem">{{ entry.form_definition.name }}</div></AccordionTrigger
                >
                <AccordionContent>
                    <ChecklistEntry :entry="entry" :advice-id="adviceId" />
                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <div v-if="availableChecklists.length > 0" style="margin-top: 8px">
            <Button variant="outline" size="sm" @click="dialogOpen = true">+ Checkliste hinzufügen</Button>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Checkliste hinzufügen</DialogTitle>
                </DialogHeader>
                <div class="checklist-dialog__list">
                    <button
                        v-for="checklist in availableChecklists"
                        :key="checklist.id"
                        class="checklist-dialog__item"
                        :disabled="adding"
                        @click="addChecklist(checklist.id)"
                    >
                        <span class="checklist-dialog__name">{{ checklist.name }}</span>
                        <span v-if="checklist.description" class="checklist-dialog__description">{{ checklist.description }}</span>
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
.checklist-dialog__list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.checklist-dialog__item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 12px 16px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    background: white;
    text-align: left;
    width: 100%;
    transition: background-color 0.15s;
}

.checklist-dialog__item:hover:not(:disabled) {
    background-color: #f8f9fa;
}

.checklist-dialog__item:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.checklist-dialog__name {
    font-weight: 500;
    font-size: 14px;
}

.checklist-dialog__description {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}
</style>
