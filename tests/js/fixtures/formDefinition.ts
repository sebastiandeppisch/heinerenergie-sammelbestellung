type FormDefinitionData = App.Data.FormDefinitionData;
type FormFieldData = App.Data.FormFieldData;
type FormToAdviceMappingData = App.Data.FormToAdviceMappingData;
type FormToMapPointMappingData = App.Data.FormToMapPointMappingData;

export function makeField(overrides: Partial<FormFieldData> & Pick<FormFieldData, 'id' | 'type'>): FormFieldData {
    return {
        label: overrides.id,
        options: [],
        placeholder: null,
        help_text: null,
        required: false,
        default_value: null,
        sort_order: 0,
        min_length: null,
        max_length: null,
        min_value: null,
        max_value: null,
        accepted_file_types: null,
        max_images: 1,
        ...overrides,
    };
}

export function makeAdviceMapping(overrides: Partial<FormToAdviceMappingData> = {}): FormToAdviceMappingData {
    return {
        enabled: true,
        first_name_field_id: null,
        last_name_field_id: null,
        address_field_id: null,
        email_field_id: null,
        phone_field_id: null,
        advice_type_field_id: null,
        advice_type_direct: null,
        advice_type_home_option_value: null,
        advice_type_virtual_option_value: null,
        default_group_id: null,
        ...overrides,
    };
}

export function makeMapPointMapping(overrides: Partial<FormToMapPointMappingData> = {}): FormToMapPointMappingData {
    return {
        enabled: true,
        title_field_id: null,
        description_field_id: null,
        coordinate_field_id: null,
        ...overrides,
    };
}

export function makeFormDefinition(overrides: Partial<FormDefinitionData> = {}): FormDefinitionData {
    return {
        id: 'form-1',
        name: 'Testformular',
        description: null,
        is_active: true,
        fields: [],
        group_id: 'group-1',
        advice_mapping: null,
        map_point_mapping: null,
        success_message: null,
        show_next_form_button: false,
        next_form_button_text: null,
        type: 0,
        allowed_embed_domains: null,
        ...overrides,
    };
}

/** The five fields the advice mapping always requires, each of the type it expects. */
export const wellTypedAdviceFields = [
    makeField({ id: 'f-first', type: 'text' }),
    makeField({ id: 'f-last', type: 'text' }),
    makeField({ id: 'f-address', type: 'address' }),
    makeField({ id: 'f-email', type: 'email' }),
    makeField({ id: 'f-phone', type: 'phone' }),
];

export const completeAdviceMapping = makeAdviceMapping({
    first_name_field_id: 'f-first',
    last_name_field_id: 'f-last',
    address_field_id: 'f-address',
    email_field_id: 'f-email',
    phone_field_id: 'f-phone',
    advice_type_direct: '0',
});
