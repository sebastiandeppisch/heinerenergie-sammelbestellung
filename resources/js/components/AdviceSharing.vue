<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import { useFilter } from 'reka-ui';
import { Button } from '@/shadcn/components/ui/button';
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxList } from '@/shadcn/components/ui/combobox';
import { Label } from '@/shadcn/components/ui/label';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/shadcn/components/ui/tags-input';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    adviceId: string;
    sharedIds: string[];
}>();

type Advisor = App.Data.UserData;

const advisors = ref<Advisor[]>([]);
const selectedIds = ref<string[]>(props.sharedIds.map(String));
const savedIds = ref<string[]>(props.sharedIds.map(String));
const isSaving = ref(false);
const open = ref(false);
const searchTerm = ref('');

const { contains } = useFilter({ sensitivity: 'base' });

const hasChanges = computed(() => {
    if (selectedIds.value.length !== savedIds.value.length) {
        return true;
    }
    return selectedIds.value.some((id) => !savedIds.value.includes(id));
});

onMounted(async () => {
    const { data } = await axios.get<Advisor[]>('/api/users?withoutself=true');
    advisors.value = data;
});

const availableAdvisors = computed(() => advisors.value.filter((a) => !selectedIds.value.includes(String(a.id))));

const filteredAdvisors = computed(() =>
    searchTerm.value ? availableAdvisors.value.filter((a) => contains(a.name, searchTerm.value)) : availableAdvisors.value,
);

function getAdvisorName(id: string): string {
    return advisors.value.find((a) => String(a.id) === id)?.name ?? id;
}

function addAdvisor(id: string | null) {
    if (id && !selectedIds.value.includes(id)) {
        selectedIds.value = [...selectedIds.value, id];
        searchTerm.value = '';
        if (filteredAdvisors.value.length === 0) {
            open.value = false;
        }
    }
}

async function saveAdvisors() {
    isSaving.value = true;
    try {
        await axios.post('/api/advices/' + props.adviceId + '/advisors', { advisors: selectedIds.value });
        savedIds.value = [...selectedIds.value];
        toast.success('Beratungsteam aktualisiert', { duration: 2000 });
    } catch (error) {
        toast.error('Fehler beim Speichern', { duration: 2000 });
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <div class="sharing-container space-y-4">
        <div>
            <Label class="mb-2 block">Teilen mit</Label>
            <Combobox v-model:open="open" :ignore-filter="true">
                <ComboboxAnchor as-child>
                    <TagsInput v-model="selectedIds" :display-value="(v) => getAdvisorName(String(v))" class="w-full flex-col items-stretch gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <TagsInputItem v-for="id in selectedIds" :key="id" :value="id">
                                <TagsInputItemText />
                                <TagsInputItemDelete />
                            </TagsInputItem>
                        </div>

                        <ComboboxInput v-model="searchTerm" as-child>
                            <TagsInputInput
                                placeholder="Berater:in suchen..."
                                class="h-auto w-full border-none p-0 focus-visible:ring-0"
                                @keydown.enter.prevent
                            />
                        </ComboboxInput>
                    </TagsInput>

                    <ComboboxList class="w-[--reka-popper-anchor-width]">
                        <ComboboxEmpty>Keine Berater:in gefunden</ComboboxEmpty>
                        <ComboboxGroup>
                            <ComboboxItem
                                v-for="advisor in filteredAdvisors"
                                :key="advisor.id"
                                :value="advisor.id"
                                @select.prevent="addAdvisor(advisor.id)"
                            >
                                {{ advisor.name }}
                            </ComboboxItem>
                        </ComboboxGroup>
                    </ComboboxList>
                </ComboboxAnchor>
            </Combobox>
        </div>

        <div class="flex justify-end">
            <Button :disabled="isSaving || !hasChanges" @click="saveAdvisors">
                {{ isSaving ? 'Wird gespeichert...' : 'Speichern' }}
            </Button>
        </div>

        <div class="sharing-info">
            <div class="info-icon">
                <font-awesome-icon icon="fa fa-info-circle" />
            </div>
            <p class="info-text">Du kannst diese Beratung mit anderen Berater*innen teilen, um die Beratung gemeinsam durchzuführen</p>
        </div>
    </div>
</template>

<style scoped>
.sharing-container {
    padding: 0 24px 24px;
    padding-top: 16px;
}

.sharing-info {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    margin-top: 12px;
}

.info-icon {
    color: #3498db;
    font-size: 16px;
    margin-top: 2px;
}

.info-text {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: #6c757d;
}
</style>
