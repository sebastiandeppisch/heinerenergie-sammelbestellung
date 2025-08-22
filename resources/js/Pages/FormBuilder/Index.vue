<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import { DxButton } from 'devextreme-vue/button';
import { DxColumn, DxDataGrid, DxLookup, DxPager, DxPaging, DxSearchPanel } from 'devextreme-vue/data-grid';
import notify from 'devextreme/ui/notify';
import { route } from 'ziggy-js';

const props = defineProps<{
    formDefinitions: App.Data.FormDefinitionData[];
    groups: App.Data.GroupData[];
}>();

function confirmDeleteForm(formId: number) {
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

function editForm(formId: number) {
    router.visit(route('form-definitions.edit', formId));
}
</script>

<template>
    <div class="m-4">
        <div class="page-header">
            <h2 class="page-title">Formular-Verwaltung</h2>
            <div class="page-actions">
                <Button @click="createNewForm">
                    <FontAwesomeIcon :icon="faPlus" />
                    Neues Formular
                </Button>
            </div>
        </div>

        <div class="page-content">
            <div class="dx-card p-4">
                <DxDataGrid :data-source="props.formDefinitions" :show-borders="true" :column-auto-width="true">
                    <DxColumn data-field="name" caption="Name" />
                    <DxColumn data-field="group_id" caption="Initiative">
                        <DxLookup :data-source="props.groups" value-expr="id" display-expr="name" />
                    </DxColumn>
                    <DxColumn data-field="description" caption="Beschreibung" />
                    <DxColumn data-field="is_active" caption="Aktiv" data-type="boolean" />
                    <DxColumn caption="Aktionen" cell-template="action-buttons" :allow-sorting="false" :width="120" />
                    <template #action-buttons="{ data }">
                        <div class="actions-container">
                            <DxButton icon="edit" hint="Bearbeiten" @click="editForm(data.data.id)" />
                            <DxButton icon="trash" hint="Löschen" @click="confirmDeleteForm(data.data.id)" />
                        </div>
                    </template>

                    <DxPaging :page-size="10" />
                    <DxPager :visible="true" :show-page-size-selector="true" :allowed-page-sizes="[5, 10, 20]" :show-info="true" />
                    <DxSearchPanel :visible="true" :highlight-case-sensitive="false" placeholder="Suche..." />
                </DxDataGrid>
            </div>
        </div>
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

.page-content {
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.actions-container {
    display: flex;
    gap: 5px;
}
</style>
