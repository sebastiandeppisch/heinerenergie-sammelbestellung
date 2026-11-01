type MapPointConsentConfigData = App.Data.MapPointConsentConfigData;

export type ConsentTextSegment = { text: string; url?: string };

/**
 * The labels of the details the consent covers for the chosen sub category, characteristics and location, in the order
 * the server uses: title and description, the location, the fields of the category, then those of each characteristic.
 * The location counts only once it is filled in, like on the server.
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

    const location = config.location_field_id === null ? null : values[config.location_field_id];
    const locationLabels = typeof location === 'string' && location.trim() !== '' ? [config.location_label] : [];

    return [...config.base_labels, ...locationLabels, ...categoryLabels, ...characteristicLabels];
}

/**
 * The text of the chosen level, split so that the privacy policy in the revocation sentence can be a link.
 */
export function consentText(config: MapPointConsentConfigData, values: Record<string, unknown>, details: boolean): ConsentTextSegment[] {
    const template = details ? config.details_text.replaceAll('{felder}', consentLabels(config, values).join(', ')) : config.point_text;
    const [before, ...rest] = template.split('{widerruf}');
    const segments: ConsentTextSegment[] = [{ text: before }];

    for (const after of rest) {
        segments.push(...revocationSegments(config), { text: after });
    }

    return segments.filter((segment) => segment.text !== '');
}

function revocationSegments(config: MapPointConsentConfigData): ConsentTextSegment[] {
    const linked = 'Datenschutzerklärung';
    const index = config.revocation_text.indexOf(linked);

    if (config.revocation_url === null || index === -1) {
        return [{ text: config.revocation_text }];
    }

    return [
        { text: config.revocation_text.slice(0, index) },
        { text: linked, url: config.revocation_url },
        { text: config.revocation_text.slice(index + linked.length) },
    ];
}
