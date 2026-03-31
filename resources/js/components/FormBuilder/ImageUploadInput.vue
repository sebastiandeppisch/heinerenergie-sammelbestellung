<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent } from '@/shadcn/components/ui/dialog';
import { ImageIcon, XIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        maxImages?: number;
        disabled?: boolean;
        hasError?: boolean;
        storedPaths?: string[];
    }>(),
    {
        maxImages: 1,
        disabled: false,
        hasError: false,
        storedPaths: () => [],
    },
);

const modelValue = defineModel<(File | string)[]>({ default: () => [] });

const emit = defineEmits<{
    (e: 'change'): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const lightboxSrc = ref<string | null>(null);

const previews = computed<string[]>(() => {
    if (modelValue.value && modelValue.value.length > 0) {
        if (modelValue.value[0] instanceof File) {
            return (modelValue.value as File[]).map((file) => URL.createObjectURL(file));
        }
        return (modelValue.value as string[]).map((path) => `/storage/${path}`);
    }
    return props.storedPaths.map((path) => `/storage/${path}`);
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
</script>

<template>
    <div class="space-y-3">
        <div v-if="previews.length > 0" class="flex flex-wrap gap-3">
            <div v-for="(src, index) in previews" :key="index" class="group relative">
                <img
                    :src="src"
                    alt="Vorschau"
                    class="h-24 w-24 rounded-md border object-cover"
                    :class="{ 'cursor-zoom-in': disabled }"
                    @click="disabled ? (lightboxSrc = src) : undefined"
                />
                <button
                    v-if="!disabled"
                    type="button"
                    @click="removeFile(index)"
                    class="absolute -top-2 -right-2 hidden rounded-full bg-destructive p-0.5 text-destructive-foreground group-hover:flex"
                >
                    <XIcon class="h-3 w-3" />
                </button>
            </div>
        </div>

        <div v-if="!disabled && canAddMore">
            <input
                ref="inputRef"
                type="file"
                class="hidden"
                accept="image/jpeg,image/png"
                :multiple="maxImages > 1"
                @change="handleFiles"
            />
            <Button
                type="button"
                variant="outline"
                :class="{ 'border-destructive text-destructive': hasError }"
                @click="openPicker"
            >
                <ImageIcon class="mr-2 h-4 w-4" />
                {{ previews.length === 0 ? 'Bild auswählen' : 'Weiteres Bild hinzufügen' }}
            </Button>
            <p v-if="maxImages > 1" class="mt-1 text-xs text-muted-foreground">
                {{ previews.length }} / {{ maxImages }} Bilder
            </p>
        </div>
    </div>

    <Dialog :open="lightboxSrc !== null" @update:open="(open) => !open && (lightboxSrc = null)">
        <DialogContent class="max-w-screen-lg p-2">
            <img :src="lightboxSrc ?? ''" alt="Vollansicht" class="max-h-[90vh] w-full rounded object-contain" />
        </DialogContent>
    </Dialog>
</template>
