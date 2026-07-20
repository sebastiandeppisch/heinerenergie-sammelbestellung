<template>
    <div class="group-form">
        <form @submit.prevent="submit">
            <!-- Group name -->
            <div class="mb-4 space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" required :class="{ 'border-destructive': form.errors.name }" />
                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
            </div>

            <!-- Parent Group -->
            <div class="mb-4 space-y-2">
                <Label>Übergeordnete Initiative</Label>
                <Select v-model="parentIdString">
                    <SelectTrigger>
                        <SelectValue placeholder="Übergeordnete Initiative auswählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="group in parentGroups" :key="group.id" :value="String(group.id)">
                            {{ group.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <div v-if="form.errors.parent_id" class="text-sm text-red-500">{{ form.errors.parent_id }}</div>
            </div>

            <!-- Action buttons -->
            <div class="flex justify-end space-x-3">
                <Button type="submit" variant="default" :disabled="form.processing" @click="submit">
                    <Plus class="h-4 w-4" />
                    Erstellen
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

type GroupData = App.Data.GroupData;

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const props = defineProps<{
    parentGroups: GroupData[];
    parentRequired: boolean;
}>();

const form = useForm<{
    name: string;
    parent_id: number | null;
}>({
    name: '',
    parent_id: null,
});

const parentIdString = computed({
    get: () => (form.parent_id !== null ? String(form.parent_id) : undefined),
    set: (v: string | undefined) => {
        form.parent_id = v ? Number(v) : null;
    },
});

const submit = () => {
    if (form.name.length === 0) {
        toast.error('Bitte gib einen Namen für die Initiative ein.');
        return;
    }

    if (form.parent_id === null && props.parentRequired) {
        toast.error('Du kannst nur Untergruppen erstellen, wenn du eine übergeordnete Initiative auswählst.');
        return;
    }

    form.post(route('groups.store'), {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>
