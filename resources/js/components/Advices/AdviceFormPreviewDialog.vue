<script setup lang="ts">
import FormSubmissionRenderer from '@/components/FormBuilder/FormSubmissionRenderer.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import DialogScrollContent from '@/shadcn/components/ui/dialog/DialogScrollContent.vue';
import axios from 'axios';
import { Eye, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    adviceId: string;
}>();

const open = ref(false);
const isLoading = ref(false);
const hasLoaded = ref(false);
const formSubmission = ref<App.Data.FormSubmissionData | null>(null);

function setOpen(value: boolean) {
    open.value = value;
    if (value && !hasLoaded.value) {
        isLoading.value = true;
        axios
            .get(route('api.advices.formSubmission', props.adviceId))
            .then((r) => {
                formSubmission.value = r.data.formSubmission;
                hasLoaded.value = true;
            })
            .finally(() => {
                isLoading.value = false;
            });
    }
}
</script>

<template>
    <Button data-test="preview-advice-button" variant="outline" size="sm" class="h-7 text-xs" title="Vorschau" @click="setOpen(true)">
        <Eye class="h-4 w-4" />
    </Button>

    <Dialog :open="open" @update:open="setOpen">
        <DialogScrollContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Zusätzliche Informationen aus dem Formular</DialogTitle>
            </DialogHeader>

            <div v-if="isLoading" class="flex justify-center py-8">
                <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
            </div>
            <FormSubmissionRenderer v-else-if="formSubmission" :form-submission="formSubmission" />
            <p v-else class="py-4 text-sm text-muted-foreground">Für diese Beratung liegen keine Formulardaten vor.</p>
        </DialogScrollContent>
    </Dialog>
</template>
