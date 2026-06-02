<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { TagsInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/shadcn/components/ui/tags-input';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    adviceId: string;
    sharedIds: number[];
}>();

const emit = defineEmits<{
    (e: 'update:sharedIds', value: string[]): void;
}>();

interface Advisor {
    id: number;
    name: string;
}

const advisors = ref<Advisor[]>([]);
const selectedIds = ref<string[]>(props.sharedIds.map(String));
const selectKey = ref(0);

onMounted(async () => {
    const { data } = await axios.get('/api/users?withoutself=true');
    advisors.value = data;
});

const availableAdvisors = computed(() => advisors.value.filter((a) => !selectedIds.value.includes(String(a.id))));

function getAdvisorName(id: string): string {
    return advisors.value.find((a) => String(a.id) === id)?.name ?? id;
}

function addAdvisor(id: string) {
    if (id && !selectedIds.value.includes(id)) {
        const newIds = [...selectedIds.value, id];
        selectedIds.value = newIds;
        selectKey.value++;
        saveAdvisors(newIds);
    }
}

function handleTagsUpdate(newIds: string[]) {
    selectedIds.value = newIds;
    saveAdvisors(newIds);
}

function saveAdvisors(ids: string[]) {
    axios.post('/api/advices/' + props.adviceId + '/advisors', { advisors: ids.map(Number) }).then(() => {
        toast.success('Teilung aktualisiert', { duration: 2000 });
        router.reload();
    });
}
</script>

<template>
    <div class="sharing-container">
        <Label class="mb-2 block">Teilen mit</Label>
        <TagsInput :model-value="selectedIds" @update:model-value="handleTagsUpdate" class="mb-2 w-full">
            <TagsInputItem v-for="id in selectedIds" :key="id" :value="id">
                <TagsInputItemText>{{ getAdvisorName(id) }}</TagsInputItemText>
                <TagsInputItemDelete />
            </TagsInputItem>
        </TagsInput>
        <Select :key="selectKey" @update:model-value="addAdvisor">
            <SelectTrigger>
                <SelectValue placeholder="Berater:in hinzufügen..." />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="advisor in availableAdvisors" :key="advisor.id" :value="String(advisor.id)">
                    {{ advisor.name }}
                </SelectItem>
            </SelectContent>
        </Select>

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
