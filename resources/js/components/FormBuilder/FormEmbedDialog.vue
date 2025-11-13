<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Code2, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

type FormDefinitionData = App.Data.FormDefinitionData;

const props = defineProps<{
    formDefinition: FormDefinitionData;
}>();

const embedDialogOpen = ref(false);

function openEmbedDialog() {
    embedDialogOpen.value = true;
}

function copyIframeCode() {
    if (props.formDefinition?.id) {
        navigator.clipboard
            .writeText(iframeCode.value)
            .then(() => {
                toast.success('Kopiert', {
                    description: 'Iframe-Code wurde in die Zwischenablage kopiert',
                });
            })
            .catch(() => {
                toast.error('Fehler', {
                    description: 'Iframe-Code konnte nicht kopiert werden',
                });
            });
    }
}

const iframeCode = computed(() => {
    const embedJsUrl = window.location.origin + '/js/embed-iframe.js';
    const widgetUrl = route('form.show', props.formDefinition.id);
    return '<scr' + 'ipt src="' + embedJsUrl + '" data-widget-url="' + widgetUrl + '"></scr' + 'ipt>';
});
</script>

<template>
    <!-- Einbetten Button -->
    <Button @click="openEmbedDialog" variant="outline">
        Einbetten
        <Code2 />
    </Button>

    <!-- Einbetten Dialog -->
    <Dialog v-model:open="embedDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Formular einbetten</DialogTitle>
                <DialogDescription> Kopiere den folgenden iframe-Code, um das Formular in Deine Webseite einzubetten. </DialogDescription>
            </DialogHeader>
            <div class="flex items-center space-x-2">
                <div class="grid flex-1 gap-2">
                    <Textarea :model-value="iframeCode" readonly class="min-h-[120px] font-mono text-sm" />
                </div>
            </div>
            <DialogFooter class="sm:justify-start">
                <Button @click="copyIframeCode" type="button" variant="secondary">
                    <Copy class="mr-2 h-4 w-4" />
                    Kopieren
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
