type MapPointConsentConfigData = App.Data.MapPointConsentConfigData;

/**
 * The labels of the details the consent covers for the chosen sub category and characteristics, in the order the
 * server uses: title and description, the fields of the category, then the fields of each characteristic.
 */
export function consentLabels(config: MapPointConsentConfigData, values: Record<string, unknown>): string[] {
    const selectedOption = config.subcategory_field_id === null ? null : values[config.subcategory_field_id];
    const categoryKey = typeof selectedOption === 'string' && selectedOption in config.category_labels ? selectedOption : '';
    const categoryLabels = categoryKey === '' ? config.default_category_labels : config.category_labels[categoryKey];

    const checked = config.characteristics_field_id === null ? [] : values[config.characteristics_field_id];
    const checkedOptions = Array.isArray(checked) ? checked.map(String) : [];
    const characteristicLabels = config.characteristics
        .filter((characteristic) => checkedOptions.includes(characteristic.option_value) && characteristic.selectable_for.includes(categoryKey))
        .flatMap((characteristic) => characteristic.labels);

    return [...config.base_labels, ...categoryLabels, ...characteristicLabels];
}

export function consentText(config: MapPointConsentConfigData, values: Record<string, unknown>): string {
    return config.text.replaceAll('{felder}', consentLabels(config, values).join(', '));
}
