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

            <!-- Group logo -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Logo</label>
                <div class="flex items-center">
                    <img :src="logoSrc" :alt="group.name" class="mr-4 h-12 w-12 rounded" />
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

type FormData = Omit<GroupData, 'id' | 'logo_path' | 'parent_id' | 'users_count' | 'advices_count' | 'userCanActAsAdmin' | 'new_advice_mail'> & {
    logo: File | null;
    remove_logo: boolean;
    _method: string;
};

const form = useForm<FormData>({
    name: props.group.name,
    description: props.group.description,
    accepts_transfers: props.group.accepts_transfers,
    logo: null,
    remove_logo: false,
    _method: 'PUT',
});

// Computed property to handle null to undefined conversion for description
const formDescription = computed({
    get: () => form.description ?? undefined,
    set: (value) => (form.description = value || null),
});

const logoInput = ref<HTMLInputElement | null>(null);

const handleLogoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
    }
};

const removeLogo = () => {
    form.logo = null;
    if (logoInput.value) {
        logoInput.value.value = '';
    }
    form.remove_logo = true;
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
