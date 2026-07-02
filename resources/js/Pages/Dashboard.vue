<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import CardHeader from '@/shadcn/components/ui/card/CardHeader.vue';
import type { CustomPageProps } from '@/types/pageProps';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, Save, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';

import KpiDashboard from '@/components/KpiDashboard.vue';

interface Props {
    advisorInfo: string;
}

const props = defineProps<Props>();
const page = usePage<CustomPageProps>();

const currentGroup = computed(() => page.props.auth.currentGroup);
const isAdmin = computed(() => page.props.auth.user?.is_acting_as_admin ?? false);
const canEdit = computed(() => currentGroup.value !== null && isAdmin.value);

const isEditMode = ref(false);
const state = reactive({
    value: props.advisorInfo || '',
    dirty: false,
    saving: false,
});

const onValueChanged = () => {
    state.dirty = true;
};

const save = () => {
    if (!currentGroup.value || !state.dirty) return;

    state.saving = true;
    router.put(
        route('groups.dashboard-info.update', currentGroup.value.id),
        {
            dashboard_info: state.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                state.dirty = false;
                state.saving = false;
                isEditMode.value = false;
            },
            onError: () => {
                state.saving = false;
            },
        },
    );
};

const startEdit = () => {
    state.value = props.advisorInfo || '';
    state.dirty = false;
    isEditMode.value = true;
};

const cancelEdit = () => {
    state.value = props.advisorInfo || '';
    state.dirty = false;
    isEditMode.value = false;
};
</script>

<template>
    <div ref="outer">
        <h2 class="content-block m-6">Dashboard</h2>
        <Card class="mx-6">
            <CardHeader class="flex flex-row items-center justify-between">
                <h2>Berater*innen-Infos</h2>
                <Button v-if="canEdit && !isEditMode" variant="outline" size="icon" @click="startEdit">
                    <Edit class="h-4 w-4" />
                </Button>
            </CardHeader>
            <CardContent>
                <div v-if="!isEditMode" v-html="props.advisorInfo"></div>
                <div v-else class="space-y-4">
                    <RichTextEditor v-model="state.value" @change="onValueChanged" />
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="cancelEdit" :disabled="state.saving">
                            <X class="mr-2 h-4 w-4" />
                            Abbrechen
                        </Button>
                        <Button @click="save" :disabled="!state.dirty || state.saving">
                            <Save class="mr-2 h-4 w-4" />
                            {{ state.saving ? 'Wird gespeichert...' : 'Speichern' }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="m-6">
            <KpiDashboard />
        </div>
    </div>
</template>
