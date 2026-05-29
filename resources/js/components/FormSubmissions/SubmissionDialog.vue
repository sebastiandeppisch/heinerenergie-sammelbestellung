<template>
    <Dialog :open="open" @update:open="(value: boolean) => emit('update:open', value)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ submission?.form_name }}</DialogTitle>
            </DialogHeader>
            <SubmissionCard
                v-if="submission"
                :submission="submission"
                :index="index"
                :show-group-header="false"
                :show-group-badge="false"
                variant="dialog"
            />
        </DialogContent>
    </Dialog>
</template>

<script lang="ts" setup>
import SubmissionCard from '@/components/FormSubmissions/SubmissionCard.vue';
import Dialog from '@/shadcn/components/ui/dialog/Dialog.vue';
import DialogContent from '@/shadcn/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/shadcn/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/shadcn/components/ui/dialog/DialogTitle.vue';

defineProps<{
    open: boolean;
    submission: App.Data.FormSubmissionData | null;
    index: number | string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();
</script>
