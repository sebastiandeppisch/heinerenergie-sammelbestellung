<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogTitle } from '@/shadcn/components/ui/dialog';
import { XIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        alt?: string;
    }>(),
    { alt: '' },
);

/** The image shown in full size, null while the lightbox is closed. */
const src = defineModel<string | null>('src', { required: true });
</script>

<template>
    <Dialog :open="src !== null" @update:open="(open) => !open && (src = null)">
        <!-- The default close button sits unshaded on the image, so it is hidden in favour of one with a visible background. -->
        <DialogContent class="max-w-screen-lg p-2 [&>button:last-child]:hidden">
            <DialogTitle class="sr-only">Bild</DialogTitle>
            <img v-if="src" :src="src" :alt="alt" class="max-h-[90vh] w-full rounded object-contain" />
            <DialogClose as-child>
                <Button
                    type="button"
                    variant="secondary"
                    size="icon-sm"
                    class="absolute top-4 right-4 rounded-full bg-background/90 shadow-lg ring-1 shadow-black/40 ring-black/10 backdrop-blur-sm hover:bg-background"
                    aria-label="Schließen"
                >
                    <XIcon />
                </Button>
            </DialogClose>
        </DialogContent>
    </Dialog>
</template>
