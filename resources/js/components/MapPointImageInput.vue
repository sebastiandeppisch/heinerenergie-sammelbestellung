<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { GripVertical, ImageIcon, XIcon } from '@lucide/vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        /** The images the field holds, to show the entries that keep them. */
        storedImages?: Array<App.Data.ImageData>;
        maxImages?: number;
        hasError?: boolean;
    }>(),
    {
        storedImages: () => [],
        maxImages: 1,
        hasError: false,
    },
);

/** File names keep stored images, files are uploaded. The order is the order of the images. */
const entries = defineModel<Array<string | File>>({ default: () => [] });

const container = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const objectUrls = new Map<File, string>();

useSortable(container, entries, { animation: 150, handle: '.image-drag-handle' });

const canAddMore = computed(() => entries.value.length < props.maxImages);

function key(entry: string | File): string {
    return typeof entry === 'string' ? entry : `${entry.name}-${entry.size}-${entry.lastModified}`;
}

function thumbnail(entry: string | File): string {
    if (typeof entry === 'string') {
        const image = props.storedImages.find((stored) => stored.name === entry);
        return image ? `${image.url}?w=400` : '';
    }

    if (!objectUrls.has(entry)) {
        objectUrls.set(entry, URL.createObjectURL(entry));
    }
    return objectUrls.get(entry)!;
}

function addFiles(event: Event) {
    const input = event.target as HTMLInputElement;
    entries.value = [...entries.value, ...Array.from(input.files ?? [])].slice(0, props.maxImages);
    input.value = '';
}

function remove(index: number) {
    entries.value = entries.value.filter((_, i) => i !== index);
}

onBeforeUnmount(() => objectUrls.forEach((url) => URL.revokeObjectURL(url)));
</script>

<template>
    <div class="space-y-3">
        <div ref="container" class="flex flex-wrap gap-3" data-test="map-point-images">
            <div v-for="(entry, index) in entries" :key="key(entry)" class="group relative">
                <img :src="thumbnail(entry)" alt="Vorschau" class="h-24 w-24 rounded-md border object-cover" />
                <div
                    v-if="entries.length > 1"
                    class="image-drag-handle absolute bottom-1 left-1 cursor-grab rounded bg-background/80 p-0.5"
                    aria-label="Verschieben"
                >
                    <GripVertical class="h-3 w-3" />
                </div>
                <button
                    type="button"
                    class="absolute -top-2 -right-2 flex rounded-full bg-destructive p-0.5 text-destructive-foreground"
                    aria-label="Bild entfernen"
                    @click="remove(index)"
                >
                    <XIcon class="h-3 w-3" />
                </button>
            </div>
        </div>

        <div v-if="canAddMore">
            <input
                ref="inputRef"
                type="file"
                class="hidden"
                data-test="map-point-image-file"
                accept="image/jpeg,image/png"
                :multiple="maxImages > 1"
                @change="addFiles"
            />
            <Button type="button" variant="outline" :class="{ 'border-destructive text-destructive': hasError }" @click="inputRef?.click()">
                <ImageIcon class="mr-2 h-4 w-4" />
                {{ entries.length === 0 ? 'Bild auswählen' : 'Weiteres Bild hinzufügen' }}
            </Button>
        </div>
        <p v-if="maxImages > 1" class="text-xs text-muted-foreground">{{ entries.length }} / {{ maxImages }} Bilder</p>
    </div>
</template>
