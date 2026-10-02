<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Upload, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    categoryId: string;
    /** Edited characteristic, a new one is created without. */
    characteristic: App.Data.MapPointCharacteristicData | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const DEFAULT_COLOR = '#2e7d32';

const form = useForm({
    name: '',
    color: null as string | null,
    icon: null as File | null,
    remove_icon: false,
    public_field_ids: [] as Array<string>,
    _method: 'post',
});

const fileInput = ref<HTMLInputElement>();

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.defaults({
        name: props.characteristic?.name ?? '',
        color: props.characteristic?.color ?? null,
        icon: null,
        remove_icon: false,
        public_field_ids: (props.characteristic?.fields ?? []).filter((field) => field.is_public).map((field) => field.id),
        _method: props.characteristic ? 'put' : 'post',
    });
    form.reset();
    form.clearErrors();
});

const iconPreviewUrl = computed(() => {
    if (form.icon) {
        return URL.createObjectURL(form.icon);
    }

    return form.remove_icon ? null : (props.characteristic?.icon_path ?? null);
});

function selectIcon(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        form.icon = file;
        form.remove_icon = false;
    }
}

function removeIcon() {
    form.icon = null;
    form.remove_icon = true;
}

function setPublic(fieldId: string, isPublic: boolean | 'indeterminate') {
    form.public_field_ids = isPublic === true ? [...form.public_field_ids, fieldId] : form.public_field_ids.filter((id) => id !== fieldId);
}

function submit() {
    const url = props.characteristic
        ? route('mappoint-characteristics.update', props.characteristic.id)
        : route('mappoint-categories.characteristics.store', props.categoryId);

    // Sent as form data only with an icon, the method is spoofed with _method either way.
    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form class="space-y-4" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ characteristic ? 'Maßnahme bearbeiten' : 'Neue Maßnahme' }}</DialogTitle>
                    <DialogDescription>Maßnahmen lassen sich den Punkten dieser Kategorie und ihrer Unterkategorien zuweisen.</DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <Label for="characteristic_name">Name</Label>
                    <Input id="characteristic_name" v-model="form.name" required placeholder="z. B. Igeltor" />
                    <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="characteristic_color">Farbe</Label>
                    <div class="flex items-center gap-2">
                        <input
                            id="characteristic_color"
                            type="color"
                            class="h-9 w-14 cursor-pointer rounded border"
                            :value="form.color ?? DEFAULT_COLOR"
                            @input="form.color = ($event.target as HTMLInputElement).value"
                        />
                        <span class="text-sm text-gray-500">{{ form.color ?? 'Keine Farbe' }}</span>
                        <Button v-if="form.color" type="button" variant="ghost" size="sm" @click="form.color = null">Keine Farbe</Button>
                    </div>
                    <p v-if="form.errors.color" class="text-sm text-red-500">{{ form.errors.color }}</p>
                </div>

                <div class="space-y-2">
                    <Label>Symbol</Label>
                    <div class="flex items-center gap-2">
                        <div v-if="iconPreviewUrl" class="flex h-10 w-10 items-center justify-center overflow-hidden rounded bg-gray-100">
                            <img :src="iconPreviewUrl" alt="Symbol" class="h-full w-full object-contain" />
                        </div>
                        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="selectIcon" />
                        <Button type="button" variant="outline" size="sm" @click="fileInput?.click()">
                            <Upload class="h-4 w-4" />
                            {{ iconPreviewUrl ? 'Symbol ersetzen' : 'Symbol hochladen' }}
                        </Button>
                        <Button v-if="iconPreviewUrl" type="button" variant="ghost" size="sm" @click="removeIcon">
                            <X class="h-4 w-4" />
                            Entfernen
                        </Button>
                    </div>
                    <p v-if="form.errors.icon" class="text-sm text-red-500">{{ form.errors.icon }}</p>
                </div>

                <div v-if="characteristic" class="space-y-2">
                    <Label>Felder</Label>
                    <ul v-if="(characteristic.fields ?? []).length > 0" class="divide-y rounded-md border">
                        <li v-for="field in characteristic.fields" :key="field.id" class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                            <span>{{ field.label }}</span>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :id="`characteristic_public_${field.id}`"
                                    :model-value="form.public_field_ids.includes(field.id)"
                                    @update:model-value="(checked) => setPublic(field.id, checked)"
                                />
                                <Label :for="`characteristic_public_${field.id}`" class="font-normal">öffentlich</Label>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500 italic">Diese Maßnahme hat noch keine Felder.</p>
                    <p class="text-xs text-gray-500">Nur Felder mit Haken bei „öffentlich“ erscheinen auf der öffentlichen Karte.</p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Abbrechen</Button>
                    <Button type="submit" :disabled="form.processing">{{ characteristic ? 'Speichern' : 'Anlegen' }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
