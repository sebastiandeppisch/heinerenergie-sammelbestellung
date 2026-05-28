<script setup lang="ts">
import CreateAdviceDialog from '@/components/CreateAdviceDialog.vue';
import { computed, onMounted, reactive, ref } from 'vue';
import LaravelDataSource from '../LaravelDataSource';
import { AdaptTableHeight } from '../helpers';
import PhysicalValue from '../views/PhysicalValue.vue';

import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { DxSwitch } from 'devextreme-vue';
import DxButton from 'devextreme-vue/button';
import DxDataGrid, {
    DxColumn,
    DxEditing,
    DxFilterRow,
    DxGrouping,
    DxGroupItem,
    DxGroupPanel,
    DxItem,
    DxLookup,
    DxScrolling,
    DxSummary,
    DxButton as DxTableButton,
    DxToolbar,
    DxTotalItem,
} from 'devextreme-vue/data-grid';
import Store from 'devextreme/data/abstract_store';
import { default as ArrayDataSource, default as ArrayStore } from 'devextreme/data/array_store';
import CustomStore from 'devextreme/data/custom_store';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import LaravelLookupSource from '../LaravelLookupSource';
import { isActingAsAdmin, user } from '../authHelper';

const emit = defineEmits(['selectAdviceId']);

const advisors = new LaravelDataSource('api/users');
const adviceStatus = new LaravelLookupSource('api/advicestatus');
const adviceTypes = new LaravelLookupSource('api/advicetypes');

const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const reactiveHeight = tableHeight.getReactive();

const props = defineProps<{
    onlyOneGroup: boolean;
    advices: App.Models.Advice[];
    groups: App.Data.GroupData[];
}>();

onMounted(() => {
    advisors.reload();
    adviceStatus.load();
    adviceTypes.load();
    tableHeight.calcHeight();
});

const advices = computed(() => {
    return new ArrayStore({
        data: props.advices,
        key: 'id',
        onUpdated: (key, values) => {
            return axios.put(route('api.advices.update', key), values);
        },
    });
});

const groups = computed(() => {
    return new ArrayStore({
        data: props.groups,
        key: 'id',
    });
});

const r = reactive({
    advisorNames: new Map<number, string>(),
    autoExpand: false,
});

advisors.load().then(() => {
    advisors.items().forEach((a) => {
        r.advisorNames.set(a.id, a.name);
    });
});

function openAdvice(e: { row: any }) {
    router.get(route('advices.show', e.row.data.id));
}

function assignAdvice(id: number) {
    axios
        .post('api/advices/' + id + '/assign')
        .then((response) => response.data)
        .then(() => {
            router.reload();
            toast.success('Die Beratung wurde Dir zugewiesen', { duration: 3000 });
        });
}

function isOpenVisible(e: { row: any }): boolean {
    const advice = e.row.data as App.Data.DataProtectedAdviceData;
    return userCanEdit(advice);
}

function rowCanBeEdited(e: { row: any }): boolean {
    const advice = e.row.data as App.Data.DataProtectedAdviceData;
    return userCanEdit(advice);
}

function userCanEdit(advice: App.Data.DataProtectedAdviceData) {
    const userId = user.value.id;
    if (isActingAsAdmin.value) {
        return true;
    }
    if (advice.advisor_id === userId) {
        return true;
    }
    if ('shares_ids' in advice && advice.shares_ids !== undefined && advice.shares_ids.includes(userId)) {
        return true;
    }
    return false;
}

function sortedAdvisors(e: { key: string }): Store {
    if (!e.key) {
        const promiseUnsorted = axios.get('api/users').then((response) => response.data);

        return new CustomStore({
            key: 'id',
            cacheRawData: true,
            load: () => {
                return promiseUnsorted;
            },
            byKey: (key) => {
                return promiseUnsorted.then((data: any[]) => data.find((item) => item.id === key));
            },
        });
    }

    const promiseSorted = axios.get('api/advices/' + e.key + '/advisors').then((response) => response.data);

    return new CustomStore({
        key: 'id',
        cacheRawData: true,
        load: () => {
            return promiseSorted;
        },
        byKey: (key) => {
            return promiseSorted.then((data: any[]) => data.find((item) => item.id === key));
        },
    });
}

const adviceStatusResult = new ArrayDataSource([
    { id: 0, name: 'Offen' },
    { id: 1, name: 'In Bearbeitung' },
    { id: 2, name: 'Fertig - erfolgreich' },
    { id: 3, name: 'Fertig - nicht erfolgreich' },
] as any);
</script>

<template>
    <div ref="outer">
        <h2 class="content-block">Beratungen</h2>
        <div class="main-table">
            <DxDataGrid
                class="dx-card wide-card"
                :data-source="advices"
                :show-borders="false"
                :column-auto-width="true"
                :column-hiding-enabled="true"
                :height="reactiveHeight.height"
                min-height="450px"
                :allow-column-reordering="true"
                :remote-operations="false"
            >
                <DxGroupPanel :visible="true" empty-panel-text="Spalten hierher ziehen, zum Gruppieren" />
                <DxGrouping :auto-expand-all="r.autoExpand" />
                <DxScrolling mode="virtual" />
                <DxFilterRow :visible="true" />
                <DxEditing :allow-updating="rowCanBeEdited" :allow-adding="false" :allow-deleting="false" mode="cell" />
                <DxToolbar>
                    <DxItem location="before" name="groupPanel" />
                    <DxItem location="before" template="autoexpand" />
                    <DxItem location="after" template="newadvice" />
                </DxToolbar>
                <template #newadvice>
                    <CreateAdviceDialog :groups="props.groups" />
                </template>
                <template #autoexpand>
                    <div>
                        <DxSwitch v-model:value="r.autoExpand" />
                        <span style="position: relative; top: -8px; opacity: 60%"> Gruppen aufklappen </span>
                    </div>
                </template>
                <DxColumn type="buttons" caption="Öffnen">
                    <DxTableButton hint="Beratung öffnen" icon="user" text="Öffnen" @click="openAdvice" :visible="isOpenVisible" />
                </DxColumn>
                <DxColumn v-if="!onlyOneGroup" data-field="group_id" caption="Gruppe" :allow-editing="false">
                    <DxLookup :data-source="groups" display-expr="name" value-expr="id" />
                </DxColumn>
                <DxColumn data-field="created_at" caption="Erstellt am" data-type="date" :allow-editing="false" sort-order="desc" />
                <DxColumn data-field="advice_status_id" caption="Status">
                    <DxLookup :data-source="adviceStatus" display-expr="name" value-expr="id" />
                </DxColumn>
                <DxColumn data-field="result" caption="Zusammenfassung" :allow-editing="false">
                    <DxLookup :data-source="adviceStatusResult" display-expr="name" value-expr="id" />
                </DxColumn>
                <DxColumn data-field="advisor_id" caption="Berater*in" v-if="isActingAsAdmin" width="350px">
                    <DxLookup :data-source="sortedAdvisors" display-expr="name" value-expr="id" />
                </DxColumn>
                <DxColumn
                    data-field="advisor_id"
                    caption="Berater*in"
                    :allow-editing="false"
                    cell-template="simpleadvisorassignment"
                    v-if="!isActingAsAdmin"
                >
                    <DxLookup :data-source="sortedAdvisors" display-expr="name" value-expr="id" />
                </DxColumn>
                <DxColumn data-field="distance" caption="Luftlinie" cell-template="distance" :allow-editing="false" />
                <DxColumn data-field="type" caption="Typ" cell-template="typeIcon">
                    <DxLookup :data-source="adviceTypes" display-expr="name" value-expr="id" />
                </DxColumn>
                <DxColumn data-field="first_name" caption="Vorname" :allow-editing="false" />
                <DxColumn data-field="last_name" caption="Nachname" :allow-editing="false" />
                <DxColumn data-field="email" caption="E-Mail Adresse" :allow-editing="false" cell-template="filledOrPrivate" />
                <DxColumn data-field="phone" caption="Telefonnummer" :allow-editing="false" cell-template="filledOrPrivate" />
                <DxColumn data-field="street" caption="Straße & Nr." :allow-editing="false" cell-template="street" />
                <DxColumn data-field="zip" caption="Plz" :allow-editing="false" />
                <DxColumn data-field="city" caption="Stadt" :allow-editing="false" />

                <DxSummary :recalculate-while-editing="true">
                    <DxTotalItem column="advice_status_id" summary-type="count" />
                    <DxGroupItem summary-type="count" display-format="{0} Beratungen" />
                </DxSummary>
                <template #distance="{ data }">
                    <PhysicalValue :value="data.data.distance" unit="m" />
                </template>
                <template #street="{ data }">
                    <div>{{ data.data.street }} {{ data.data.street_number }}</div>
                </template>
                <template #simpleadvisorassignment="{ data }">
                    <div v-if="data.data.advisor_id !== null">{{ r.advisorNames.get(data.data.advisor_id) }}</div>
                    <div v-else>
                        <DxButton text="Übernehmen" @click="assignAdvice(data.data.id)" type="default" />
                    </div>
                </template>
                <template #typeIcon="{ data }">
                    <div v-if="data.data.type === 0"><i class="dx-icon dx-icon-home"></i></div>
                    <div v-else-if="data.data.type === 1"><i class="dx-icon dx-icon-tel"></i></div>
                    <div v-else-if="data.data.type === 2"><i class="dx-icon dx-icon-cart"></i></div>
                    <div v-else>??</div>
                </template>
                <template #filledOrPrivate="{ data }">
                    <span v-if="data.value !== null">{{ data.value }}</span>
                    <span v-else style="font-style: italic; color: gray">verborgen</span>
                </template>
            </DxDataGrid>

        </div>
    </div>
</template>
<style scoped>
@media screen and (min-width: 680px) {
    .main-table {
        margin: 10px 00px 0px 0px;
    }
}
</style>
