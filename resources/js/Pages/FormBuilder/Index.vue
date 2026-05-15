<script setup lang="ts">
import CreateFromTemplateModal from '@/components/CreateFromTemplateModal.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/shadcn/components/ui/dropdown-menu';
import { Input } from '@/shadcn/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import notify from 'devextreme/ui/notify';
import { ChevronDown, Edit, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

type FormDefinitionData = App.Data.FormDefinitionData;

const props = defineProps<{
    formDefinitions: FormDefinitionData[];
    checklists: FormDefinitionData[];
    groups: App.Data.GroupData[];
}>();

const showAdviceModal = ref(false);
const showMapPointModal = ref(false);

const activeTab = ref<'forms' | 'checklists'>('forms');

const formSearch = ref('');
const checklistSearch = ref('');

const groupNameById = computed(() => {
    const map = new Map<string, string>();
    props.groups.forEach((group) => map.set(group.id, group.name));
    return map;
});

function filterByQuery(items: FormDefinitionData[], query: string): FormDefinitionData[] {
    const q = query.trim().toLowerCase();
    if (q === '') {
        return items;
    }
    return items.filter((item) => {
        const name = item.name?.toLowerCase() ?? '';
        const description = item.description?.toLowerCase() ?? '';
        const groupName = groupNameById.value.get(item.group_id)?.toLowerCase() ?? '';
        return name.includes(q) || description.includes(q) || groupName.includes(q);
    });
}

const filteredForms = computed(() => filterByQuery(props.formDefinitions, formSearch.value));
const filteredChecklists = computed(() => filterByQuery(props.checklists, checklistSearch.value));

function confirmDeleteForm(formId: string) {
    if (confirm('Möchtest Du wirklich dieses Formular löschen?')) {
        router.delete(route('form-definitions.destroy', formId), {
            onSuccess: () => {
                notify('Formular wurde gelöscht', 'success', 2000);
            },
            onError: (errors) => {
                notify(`Fehler beim Löschen des Formulars: ${Object.values(errors).join(', ')}`, 'error', 4000);
            },
        });
    }
}

function createNewForm() {
    router.visit(route('form-definitions.create'));
}

function createNewChecklist() {
    router.visit(route('form-definitions.create', { type: 1 }));
}

function editForm(formId: string) {
    router.visit(route('form-definitions.edit', formId));
}

function openAdviceTemplate() {
    showAdviceModal.value = true;
}
</script>

<template>
    <div class="m-4">
        <div class="page-header">
            <h2 class="page-title">Formular-Verwaltung</h2>
            <div class="page-actions">
                <DropdownMenu v-if="activeTab === 'forms'">
                    <DropdownMenuTrigger as-child>
                        <Button>
                            <FontAwesomeIcon :icon="faPlus" />
                            Neues Formular
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="createNewForm">Leeres Formular</DropdownMenuItem>
                        <DropdownMenuItem @click="openAdviceTemplate">Beratungsformular</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <Button v-else @click="createNewChecklist">
                    <FontAwesomeIcon :icon="faPlus" />
                    Neue Checkliste
                </Button>
            </div>
        </div>

        <CreateFromTemplateModal v-model:open="showAdviceModal" template-type="advice" :groups="props.groups" />
        <CreateFromTemplateModal v-model:open="showMapPointModal" template-type="map_point" :groups="props.groups" />

        <Tabs v-model="activeTab">
            <TabsList>
                <TabsTrigger value="forms">Formulare ({{ formDefinitions.length }})</TabsTrigger>
                <TabsTrigger value="checklists">Checklisten ({{ checklists.length }})</TabsTrigger>
            </TabsList>

            <TabsContent value="forms">
                <Card>
                    <CardContent class="p-4">
                        <div class="mb-4">
                            <Input v-model="formSearch" placeholder="Suche..." class="max-w-sm" />
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Initiative</TableHead>
                                    <TableHead>Beschreibung</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="form in filteredForms" :key="form.id">
                                    <TableCell class="font-medium">{{ form.name }}</TableCell>
                                    <TableCell>{{ groupNameById.get(form.group_id) ?? '-' }}</TableCell>
                                    <TableCell class="text-gray-600">{{ form.description ?? '-' }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="form.is_active ? 'default' : 'secondary'">
                                            {{ form.is_active ? 'Aktiv' : 'Inaktiv' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" @click="editForm(form.id)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="confirmDeleteForm(form.id)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredForms.length === 0">
                                    <TableCell colspan="5" class="py-8 text-center text-gray-500">
                                        {{ formSearch ? 'Keine Formulare gefunden' : 'Noch keine Formulare erstellt' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="checklists">
                <Card>
                    <CardContent class="p-4">
                        <div class="mb-4">
                            <Input v-model="checklistSearch" placeholder="Suche..." class="max-w-sm" />
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Initiative</TableHead>
                                    <TableHead>Beschreibung</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="checklist in filteredChecklists" :key="checklist.id">
                                    <TableCell class="font-medium">{{ checklist.name }}</TableCell>
                                    <TableCell>{{ groupNameById.get(checklist.group_id) ?? '-' }}</TableCell>
                                    <TableCell class="text-gray-600">{{ checklist.description ?? '-' }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="checklist.is_active ? 'default' : 'secondary'">
                                            {{ checklist.is_active ? 'Aktiv' : 'Inaktiv' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" @click="editForm(checklist.id)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="confirmDeleteForm(checklist.id)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredChecklists.length === 0">
                                    <TableCell colspan="5" class="py-8 text-center text-gray-500">
                                        {{ checklistSearch ? 'Keine Checklisten gefunden' : 'Noch keine Checklisten erstellt' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.page-title {
    font-size: 24px;
    font-weight: bold;
}
</style>
