import { descendantIds } from '@/utils/categoryTree';

type FormFieldData = App.Data.FormFieldData;
type FieldType = App.Enums.FieldType;

/**
 * Sub categories an option may pick: below the category and using its fields, because the field mappings refer
 * to these fields. A sub category with own fields would replace them.
 */
export function subcategoryCandidateIds(
    categories: Array<{ id: string; name: string; parent_id: string | null }>,
    fieldsByCategory: Record<string, Array<FormFieldData>>,
    categoryId: string,
): Array<string> {
    const fieldIdsOf = (id: string) => (fieldsByCategory[id] ?? []).map((field) => field.id).join(',');

    return descendantIds(categories, categoryId).filter((id) => fieldIdsOf(id) === fieldIdsOf(categoryId));
}

/** Form fields whose type can fill the category field. */
export function sourceFieldCandidates(
    formFields: Array<FormFieldData>,
    targetField: FormFieldData,
    fieldSourceTypes: Partial<Record<FieldType, Array<FieldType>>>,
): Array<FormFieldData> {
    const sourceTypes = fieldSourceTypes[targetField.type] ?? [];

    return formFields.filter((field) => sourceTypes.includes(field.type));
}

export interface MappingTargetGroup {
    /** null for the fields of the category itself. */
    characteristicId: string | null;
    title: string;
    fields: Array<FormFieldData>;
}

/**
 * The fields a form can fill: those of the category, then those of each characteristic the category offers. Fields of a
 * characteristic are only stored when the submission picks the characteristic.
 */
export function mappingTargetGroups(
    categoryId: string,
    fieldsByCategory: Record<string, Array<FormFieldData>>,
    characteristics: Array<{ id: string; name: string }>,
    characteristicIdsByCategory: Record<string, Array<string>>,
    fieldsByCharacteristic: Record<string, Array<FormFieldData>>,
): Array<MappingTargetGroup> {
    const selectableIds = characteristicIdsByCategory[categoryId] ?? [];

    return [
        { characteristicId: null, title: 'Kategorie', fields: fieldsByCategory[categoryId] ?? [] },
        ...characteristics
            .filter((characteristic) => selectableIds.includes(characteristic.id))
            .map((characteristic) => ({
                characteristicId: characteristic.id,
                title: `Maßnahme ${characteristic.name}`,
                fields: fieldsByCharacteristic[characteristic.id] ?? [],
            })),
    ].filter((group) => group.fields.length > 0);
}
