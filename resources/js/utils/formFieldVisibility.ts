type FormFieldData = App.Data.FormFieldData;

/** The fields depending on an option, keyed by the id of the field they depend on and then by the option value. */
export type DependentFields = Map<string, Map<string, Array<FormFieldData>>>;

function conditionFieldOf(field: FormFieldData, fieldsById: Map<string, FormFieldData>): FormFieldData | undefined {
    return field.visible_if_field_id === null ? undefined : fieldsById.get(field.visible_if_field_id);
}

function hasOption(conditionField: FormFieldData, value: unknown, optionValue: string | null): boolean {
    if (conditionField.type === 'checkbox') {
        return Array.isArray(value) && value.includes(optionValue);
    }

    return value === optionValue;
}

/**
 * A field with a condition is shown when its condition field is shown and has the option. Conditions only point to
 * earlier fields, so chains always end. A condition on a field that no longer exists is ignored.
 */
export function isFieldVisible(field: FormFieldData, values: Record<string, unknown>, fieldsById: Map<string, FormFieldData>): boolean {
    const conditionField = conditionFieldOf(field, fieldsById);

    if (conditionField === undefined) {
        return true;
    }

    return hasOption(conditionField, values[conditionField.id], field.visible_if_option_value) && isFieldVisible(conditionField, values, fieldsById);
}

/** The fields shown on their own; fields with a condition are shown below the option they depend on. */
export function topLevelFields(fields: Array<FormFieldData>): Array<FormFieldData> {
    const fieldsById = new Map(fields.map((field) => [field.id, field]));

    return fields.filter((field) => conditionFieldOf(field, fieldsById) === undefined);
}

export function dependentFieldsByOption(fields: Array<FormFieldData>): DependentFields {
    const fieldsById = new Map(fields.map((field) => [field.id, field]));
    const dependents: DependentFields = new Map();

    for (const field of fields) {
        const conditionField = conditionFieldOf(field, fieldsById);

        if (conditionField === undefined || field.visible_if_option_value === null) {
            continue;
        }

        const byOption = dependents.get(conditionField.id) ?? new Map<string, Array<FormFieldData>>();
        byOption.set(field.visible_if_option_value, [...(byOption.get(field.visible_if_option_value) ?? []), field]);
        dependents.set(conditionField.id, byOption);
    }

    return dependents;
}

/** Hidden fields are sent empty, so a value typed in before unchecking the option does not end up in the submission. */
export function withoutHiddenValues(fields: Array<FormFieldData>, values: Record<string, unknown>): Record<string, unknown> {
    const fieldsById = new Map(fields.map((field) => [field.id, field]));
    const result = { ...values };

    for (const field of fields) {
        if (!isFieldVisible(field, values, fieldsById)) {
            result[field.id] = field.type === 'checkbox' || field.type === 'image' ? [] : null;
        }
    }

    return result;
}

/** Describes the condition of a field for the form builder, e.g. "Nur wenn „Maßnahmen“: Igeltor". */
export function conditionDescription(field: FormFieldData, fields: Array<FormFieldData>): string | null {
    const conditionField = conditionFieldOf(field, new Map(fields.map((candidate) => [candidate.id, candidate])));

    if (conditionField === undefined) {
        return null;
    }

    const option = conditionField.options.find((candidate) => candidate.value === field.visible_if_option_value);

    return `Nur wenn „${conditionField.label}“: ${option?.label ?? field.visible_if_option_value}`;
}

/** Removes conditions on fields or options deleted in the form builder. */
export function clearBrokenConditions(fields: Array<FormFieldData>): void {
    const fieldsById = new Map(fields.map((field) => [field.id, field]));

    for (const field of fields) {
        if (field.visible_if_field_id === null) {
            continue;
        }

        const conditionField = fieldsById.get(field.visible_if_field_id);

        if (conditionField === undefined || !conditionField.options.some((option) => option.value === field.visible_if_option_value)) {
            field.visible_if_field_id = null;
            field.visible_if_option_value = null;
        }
    }
}
