<script setup lang="ts">
import ImageLightbox from '@/components/ImageLightbox.vue';
import { Button } from '@/shadcn/components/ui/button';
import { ImageIcon, XIcon } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        maxImages?: number;
        disabled?: boolean;
        hasError?: boolean;
        /** Urls of stored images, shown while disabled and nothing is entered. */
        storedPaths?: string[];
        /** Images the field already holds. Entries keep them by their file names and can remove them. */
        storedImages?: App.Data.ImageData[];
    }>(),
    {
        maxImages: 1,
        disabled: false,
        hasError: false,
        storedPaths: () => [],
        storedImages: () => [],
    },
);

const modelValue = defineModel<(File | string)[]>({ default: () => [] });

const emit = defineEmits<{
    (e: 'change'): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const lightboxSrc = ref<string | null>(null);
const objectUrls = new Map<File, string>();

/** Stored images are served by a route that scales them down to the height the thumbnails are shown at. */
function thumbnail(url: string): string {
    return url.startsWith('blob:') ? url : `${url}?h=192`;
}

/** A file name of a stored image, or a url when submitted values are shown. */
function sourceOf(entry: File | string): string {
    if (entry instanceof File) {
        if (!objectUrls.has(entry)) {
            objectUrls.set(entry, URL.createObjectURL(entry));
        }
        return objectUrls.get(entry)!;
    }

    return props.storedImages.find((image) => image.name === entry)?.url ?? entry;
}

const previews = computed<Array<{ src: string; thumbnail: string }>>(() => {
    const urls = modelValue.value && modelValue.value.length > 0 ? modelValue.value.map(sourceOf) : props.storedPaths;

    return urls.map((url) => ({ src: url, thumbnail: thumbnail(url) }));
});

const canAddMore = computed(() => (modelValue.value?.length ?? 0) < props.maxImages);

function openPicker() {
    inputRef.value?.click();
}

function handleFiles(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files) return;

    const newFiles = Array.from(input.files);
    const combined = [...(modelValue.value ?? []), ...newFiles].slice(0, props.maxImages);
    modelValue.value = combined;
    input.value = '';
    emit('change');
}

function removeFile(index: number) {
    const updated = [...(modelValue.value ?? [])];
    updated.splice(index, 1);
    modelValue.value = updated;
    emit('change');
}

onBeforeUnmount(() => objectUrls.forEach((url) => URL.revokeObjectURL(url)));
</script>

<template>
    <div class="space-y-3">
        <div v-if="previews.length > 0" class="flex flex-wrap gap-3">
            <div v-for="(preview, index) in previews" :key="index" class="group relative">
                <img
                    :src="preview.thumbnail"
                    alt="Vorschau"
                    class="h-24 w-auto max-w-full rounded-md border object-contain"
                    :class="{ 'cursor-zoom-in': disabled }"
                    @click="disabled ? (lightboxSrc = preview.src) : undefined"
                />
                <button
                    v-if="!disabled"
                    type="button"
                    aria-label="Bild entfernen"
                    data-test="image-upload-remove"
                    @click="removeFile(index)"
                    class="absolute -top-2 -right-2 flex rounded-full bg-destructive p-0.5 text-destructive-foreground"
                >
                    <XIcon class="h-3 w-3" />
                </button>
            </div>
        </div>

        <div v-if="!disabled && canAddMore">
            <input ref="inputRef" type="file" class="hidden" accept="image/jpeg,image/png" :multiple="maxImages > 1" @change="handleFiles" />
            <Button type="button" variant="outline" :class="{ 'border-destructive text-destructive': hasError }" @click="openPicker">
                <ImageIcon class="mr-2 h-4 w-4" />
                {{ previews.length === 0 ? 'Bild auswählen' : 'Weiteres Bild hinzufügen' }}
            </Button>
            <p v-if="maxImages > 1" class="mt-1 text-xs text-muted-foreground">{{ previews.length }} / {{ maxImages }} Bilder</p>
        </div>
    </div>

    <ImageLightbox v-model:src="lightboxSrc" alt="Vollansicht" />
</template>
