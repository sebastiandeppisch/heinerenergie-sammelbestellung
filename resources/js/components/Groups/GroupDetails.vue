<template>
    <div class="group-details pt-6">
        <div class="mb-4 flex justify-end">
            <Button v-if="canEdit" variant="ghost" @click="confirmDelete">
                <Trash2 class="h-4 w-4" />
                Löschen
            </Button>
        </div>
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Group name -->
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" :disabled="!canEdit" :class="{ 'border-destructive': form.errors.name }" />
                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
            </div>

            <!-- Group description -->
            <div class="space-y-2">
                <Label for="description">Beschreibung</Label>
                <Textarea
                    id="description"
                    v-model="formDescription"
                    class="min-h-[100px]"
                    :disabled="!canEdit"
                    :class="{ 'border-destructive': form.errors.description }"
                />
                <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
            </div>

            <!-- Group URL -->
            <div class="space-y-2">
                <Label for="url">URL</Label>
                <Input
                    id="url"
                    v-model="formUrl"
                    type="url"
                    :disabled="!canEdit"
                    placeholder="https://..."
                    :class="{ 'border-destructive': form.errors.url }"
                />
                <div v-if="form.errors.url" class="text-sm text-red-500">{{ form.errors.url }}</div>
            </div>

            <!-- Group logo -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Logo</label>
                <div class="flex items-center">
                    <img :src="logoSrc" :alt="group.name" class="mr-4 h-12 max-w-12 rounded object-contain" />
                    <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="handleLogoChange" />
                    <Button type="button" variant="outline" @click="logoInput?.click()" v-if="canEdit">
                        <Upload class="h-4 w-4" />
                        Logo auswählen
                    </Button>
                    <Button type="button" v-if="logoSrc && !form.remove_logo && canEdit" variant="outline" class="ml-2" @click="removeLogo">
                        <X class="h-4 w-4" />
                        Logo Entfernen
                    </Button>
                </div>
                <div v-if="form.errors.logo" class="text-sm text-red-500">{{ form.errors.logo }}</div>
            </div>

            <!-- Group marker -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Berater:in Kartenmarker</label>
                <div class="flex items-center">
                    <img :src="markerSrc" :alt="group.name + ' Marker'" class="mr-4 h-12 max-w-12 rounded object-contain" />
                    <input type="file" ref="markerInput" class="hidden" accept="image/*" @change="handleMarkerChange" />
                    <Button type="button" variant="outline" @click="markerInput?.click()" v-if="canEdit">
                        <Upload class="h-4 w-4" />
                        Berater:in Kartenmarker auswählen
                    </Button>
                    <Button type="button" v-if="markerSrc && !form.remove_marker && canEdit" variant="outline" class="ml-2" @click="removeMarker">
                        <X class="h-4 w-4" />
                        Marker Entfernen
                    </Button>
                </div>
                <div v-if="form.errors.marker" class="text-sm text-red-500">{{ form.errors.marker }}</div>
            </div>

            <div>
                <DxCheckBox v-model="form.accepts_transfers" text="Beratungen von anderen Initiativen akzeptieren" :read-only="!canEdit" />
                <div v-if="form.errors.accepts_transfers" class="text-sm text-red-500">{{ form.errors.accepts_transfers }}</div>
            </div>

            <div class="flex justify-end">
                <Button type="button" v-if="canEdit" variant="default" @click="handleSubmit">
                    <Save class="h-4 w-4" />
                    Speichern
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { DxCheckBox } from 'devextreme-vue';
import { confirm } from 'devextreme/ui/dialog';
import { Save, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

type GroupData = App.Data.GroupData;

const props = defineProps<{
    group: GroupData;
    canEdit: boolean;
}>();

const logoSrc = computed(() => {
    if (form.logo) {
        return URL.createObjectURL(form.logo);
    }

    if (props.group.logo_path) {
        return props.group.logo_path;
    }

    return '/img/example_img.svg';
});

const markerSrc = computed(() => {
    if (form.marker) {
        return URL.createObjectURL(form.marker);
    }

    if (props.group.marker_path) {
        return props.group.marker_path;
    }

    return '/images/markers/he_yellow.svg';
});

type FormData = Omit<
    GroupData,
    'id' | 'logo_path' | 'marker_path' | 'parent_id' | 'users_count' | 'advices_count' | 'userCanActAsAdmin' | 'new_advice_mail'
> & {
    logo: File | null;
    marker: File | null;
    remove_logo: boolean;
    remove_marker: boolean;
    _method: string;
};

const form = useForm<FormData>({
    name: props.group.name,
    description: props.group.description,
    url: props.group.url ?? null,
    accepts_transfers: props.group.accepts_transfers,
    logo: null,
    marker: null,
    remove_logo: false,
    remove_marker: false,
    _method: 'PUT',
});

// Computed property to handle null to undefined conversion for description
const formDescription = computed({
    get: () => form.description ?? undefined,
    set: (value) => (form.description = value || null),
});

// Computed property to handle null to undefined conversion for url
const formUrl = computed({
    get: () => form.url ?? undefined,
    set: (value) => (form.url = value || null),
});

const logoInput = ref<HTMLInputElement | null>(null);
const markerInput = ref<HTMLInputElement | null>(null);

const handleLogoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
    }
};

const handleMarkerChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.marker = file;
        form.remove_marker = false;
    }
};

const removeLogo = () => {
    form.logo = null;
    if (logoInput.value) {
        logoInput.value.value = '';
    }
    form.remove_logo = true;
};

const removeMarker = () => {
    form.marker = null;
    if (markerInput.value) {
        markerInput.value.value = '';
    }
    form.remove_marker = true;
};

const handleSubmit = () => {
    form.post(route('groups.update', props.group.id), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const confirmDelete = () => {
    confirm('Soll diese Initiative wirklich gelöscht werden?', 'Initiative löschen').then((result) => {
        if (result) {
            form.delete(route('groups.destroy', props.group.id), {
                preserveScroll: true,
            });
        }
    });
};
</script>
