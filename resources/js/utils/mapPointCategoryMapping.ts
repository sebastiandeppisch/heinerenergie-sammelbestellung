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
