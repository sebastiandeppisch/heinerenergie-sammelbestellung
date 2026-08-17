<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Code2, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

type MapEmbedData = App.Data.MapEmbedData;

const props = defineProps<{
    mapEmbed: MapEmbedData;
}>();

const embedDialogOpen = ref(false);

function openEmbedDialog() {
    embedDialogOpen.value = true;
}

function copyIframeCode() {
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

const iframeCode = computed(() => {
    const embedJsUrl = window.location.origin + '/js/embed-iframe.js';
    const widgetUrl = route('map.public', props.mapEmbed.id);
    return '<scr' + 'ipt src="' + embedJsUrl + '" data-widget-url="' + widgetUrl + '"></scr' + 'ipt>';
});
</script>

<template>
    <!-- Einbetten Button -->
    <Button @click="openEmbedDialog" variant="outline" type="button">
        Einbetten
        <Code2 />
    </Button>

    <!-- Einbetten Dialog -->
    <Dialog v-model:open="embedDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Karte einbetten</DialogTitle>
                <DialogDescription>
                    Kopiere den folgenden iframe-Code, um die Karte in Deine Webseite einzubetten. Die Karte passt sich automatisch der Breite des
                    umgebenden Elements an. Möchtest Du eine maximale Breite vorgeben, umschließe den Code mit einem Element, das ein
                    <code>max-width</code> gesetzt hat.
                </DialogDescription>
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
