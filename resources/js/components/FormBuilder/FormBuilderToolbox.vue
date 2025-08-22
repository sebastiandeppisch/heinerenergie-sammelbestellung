<script setup lang="ts">
import Card from '@/shadcn/components/ui/card/Card.vue';

type FieldType = App.Enums.FieldType;

const FIELD_TYPES = {
    TEXT: 'text' as FieldType,
    TEXTAREA: 'textarea' as FieldType,
    NUMBER: 'number' as FieldType,
    EMAIL: 'email' as FieldType,
    PHONE: 'phone' as FieldType,
    SELECT: 'select' as FieldType,
    RADIO: 'radio' as FieldType,
    CHECKBOX: 'checkbox' as FieldType,
    FILE: 'file' as FieldType,
    DATE: 'date' as FieldType,
    GEO_COORDINATE: 'geo_coordinate' as FieldType,
};

const props = defineProps<{
    fieldTypes: FieldType[];
}>();

const emit = defineEmits<{
    (e: 'add-field', type: FieldType): void;
}>();

const getLabelForFieldType = (type: FieldType): string => {
    switch (type) {
        case FIELD_TYPES.TEXT:
            return 'Text (1 Zeile)';
        case FIELD_TYPES.TEXTAREA:
            return 'Text';
        case FIELD_TYPES.NUMBER:
            return 'Nummer';
        case FIELD_TYPES.EMAIL:
            return 'E-Mail';
        case FIELD_TYPES.PHONE:
            return 'Telefon';
        case FIELD_TYPES.SELECT:
            return 'Auswahlliste';
        case FIELD_TYPES.RADIO:
            return 'Radio Buttons';
        case FIELD_TYPES.CHECKBOX:
            return 'Checkbox';
        case FIELD_TYPES.FILE:
            return 'Datei-Upload';
        case FIELD_TYPES.DATE:
            return 'Datum';
        case FIELD_TYPES.GEO_COORDINATE:
            return 'Kartenpunkt';
        default:
            return type.toString();
    }
};

function addField(type: FieldType) {
    emit('add-field', type);
}
</script>

<template>
    <Card>
        <div class="formbuilder-toolbox">
            <h3 class="toolbox-title">Formularelemente</h3>
            <div class="toolbox-description">Klicke auf ein Element, um es zum Formular hinzuzufügen.</div>

            <div class="field-types-container">
                <div v-for="fieldType in fieldTypes" :key="fieldType" class="field-type-item" @click="addField(fieldType)">
                    <!-- <i :class="getIconForFieldType(fieldType)"></i>-->
                    <span>{{ getLabelForFieldType(fieldType) }}</span>
                </div>
            </div>
        </div>
    </Card>
</template>

<style scoped>
.formbuilder-toolbox {
    background-color: #f5f5f5;
    border-radius: 4px;
    padding: 15px;
    height: 100%;
    min-height: 500px;
}

.toolbox-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
}

.toolbox-description {
    font-size: 12px;
    color: #777;
    margin-bottom: 20px;
}

.field-types-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field-type-item {
    display: flex;
    align-items: center;
    background-color: white;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: all 0.2s;
}

.field-type-item:hover {
    background-color: #eef;
    border-color: #aaf;
}

.field-type-item i {
    margin-right: 10px;
    font-size: 16px;
    color: #337ab7;
}
</style>
