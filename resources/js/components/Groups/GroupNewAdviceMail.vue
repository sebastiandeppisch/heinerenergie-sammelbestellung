<template>
    <div class="group-new-advice-mail pt-6">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- New Advice Mail Textarea -->
            <div class="space-y-2">
                <Label for="new_advice_mail">E-Mail-Vorlage für neue Beratungen</Label>
                <Textarea
                    id="new_advice_mail"
                    v-model="form.new_advice_mail"
                    class="min-h-[300px] font-mono text-sm"
                    :disabled="!canEdit"
                    :class="{ 'border-destructive': form.errors.new_advice_mail }"
                    placeholder="E-Mail-Text, der versendet wird, wenn eine neue Beratung erstellt wird..."
                />
                <div v-if="form.errors.new_advice_mail" class="text-sm text-red-500">{{ form.errors.new_advice_mail }}</div>
                <p class="text-sm text-gray-500">
                    Dieser Text wird als E-Mail versendet, wenn eine neue Beratung erstellt wird.
                </p>
            </div>

            <div class="flex justify-end">
                <Button type="submit" v-if="canEdit" variant="default" :disabled="form.processing">
                    <Save class="h-4 w-4" />
                    Speichern
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Label } from '@/shadcn/components/ui/label';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { route } from 'ziggy-js';

type GroupData = App.Data.GroupData;

const props = defineProps<{
    group: GroupData;
    canEdit: boolean;
}>();

const form = useForm({
    new_advice_mail: props.group.new_advice_mail || '',
});

const handleSubmit = () => {
    form.put(route('groups.new-advice-mail.update', props.group.id), {
        preserveScroll: true,
    });
};
</script>

