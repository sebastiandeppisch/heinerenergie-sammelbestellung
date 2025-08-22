<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { DxButtonItem, DxForm, DxGroupItem, DxItem } from 'devextreme-vue/form';
import notify from 'devextreme/ui/notify';
import LaravelDataSource from '../LaravelDataSource';

const props = defineProps<{
    advice: App.Data.DataProtectedAdviceData;
}>();

const adviceStatus = new LaravelDataSource('/api/advicestatus');
const adviceTypes = new LaravelDataSource('/api/advicetypes');
const advicesDataSource = new LaravelDataSource('/api/advices');

function radioBoxLayout({ name }: { name: 'Home' | 'Virtual' | 'DirectOrder' }) {
    const icons = {
        Home: 'home',
        Virtual: 'tel',
        DirectOrder: 'cart',
    };

    const helpText = {
        Home: 'Beratung vor Ort',
        Virtual: 'Beratung per Telefon',
        DirectOrder: 'Direktbestellung',
    };

    return `<i style="font-size:1.5em;" class="dx-icon-${icons[name]}" title='${helpText[name]}'></i>`;
}

function onSubmit() {
    advicesDataSource
        .store()
        .update(props.advice.id, props.advice)
        .then((result) => {
            notify('Beratung gespeichert', 'success', 2000);
            router.reload();
        })
        .catch((error) => {
            notify(error, 'error', 2000);
        });
}
</script>

<template>
    <div class="" style="padding: 20px">
        <DxForm label-mode="floating" :col-count="2" :form-data="advice">
            <DxGroupItem caption="Name">
                <DxItem data-field="firstName" :label="{ text: 'Vorname' }" />
                <DxItem data-field="lastName" :label="{ text: 'Nachname' }" />
            </DxGroupItem>

            <DxGroupItem caption="Kontakt">
                <DxItem data-field="phone" :label="{ text: 'Telefonnummer' }" />
                <DxItem data-field="email" :label="{ text: 'E-Mail Adresse' }" />
            </DxGroupItem>

            <DxGroupItem caption="Adresse">
                <DxItem data-field="street" :label="{ text: 'Straße' }" />
                <DxItem data-field="streetNumber" :label="{ text: 'Hausnummer' }" />
                <DxItem data-field="zip" :label="{ text: 'Postleitzahl' }" />
                <DxItem data-field="city" :label="{ text: 'Stadt' }" />
            </DxGroupItem>

            <DxGroupItem caption="Beratung">
                <DxItem
                    data-field="advice_status_id"
                    :label="{ text: 'Status' }"
                    editor-type="dxSelectBox"
                    :editor-options="{
                        dataSource: adviceStatus,
                        displayExpr: 'name',
                        valueExpr: 'id',
                    }"
                />
                <DxItem
                    data-field="type"
                    :label="{ text: 'Typ' }"
                    editor-type="dxRadioGroup"
                    :editor-options="{
                        dataSource: adviceTypes,
                        displayExpr: 'name',
                        valueExpr: 'id',
                        layout: 'horizontal',
                        itemTemplate: radioBoxLayout,
                    }"
                />
                <DxItem
                    data-field="commentary"
                    :label="{ text: 'Kommentar' }"
                    editor-type="dxTextArea"
                    :editor-options="{ autoResizeEnabled: true }"
                />
            </DxGroupItem>

            <DxButtonItem
                :button-options="{
                    text: 'Speichern',
                    type: 'default',
                    useSubmitBehavior: true,
                    width: '100%',
                    onClick: onSubmit,
                }"
                :col-span="2"
            />
        </DxForm>
    </div>
</template>
