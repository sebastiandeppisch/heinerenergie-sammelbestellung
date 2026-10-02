import { computed, ref, watch } from 'vue';

type Field = App.Enums.MapPointSpreadsheetField;
type FieldOption = App.Data.MapPointSpreadsheetFieldData;
type PreviewRows = Array<Array<string | null>>;

export type DefaultVisibility = 'private' | 'public';

/**
 * What a column is assigned to: a field every point has, one field of a category or characteristic, or one
 * characteristic, named by its id.
 */
export type ColumnTarget = Field | `field:${string}` | `characteristic:${string}` | `new_field:${App.Enums.FieldType}`;

export type MapPointImportColumn = {
    header: string;
    field: Field;
    form_field_id?: string;
    characteristic_id?: string;
    new_field_type?: App.Enums.FieldType;
};

export type MapPointImportPayload = {
    token: string;
    key_field: Field;
    default_published: boolean;
    columns: Array<MapPointImportColumn>;
    /** Category names in the file are its sub categories, new points without a category go here. */
    main_category_id: string | null;
    /** Creates the main category with this name in the same transaction as the import. */
    new_main_category_name: string | null;
};

/** The choice of the main category: none, an existing category by id, or a new one. */
export type MainCategorySelection = 'none' | 'new' | string;

/** The types a column can create a new category field with. Fields with options are set up in the form builder. */
export const newFieldTypes: Array<{ type: App.Enums.FieldType; label: string }> = [
    { type: 'text', label: 'Text' },
    { type: 'number', label: 'Zahl' },
    { type: 'date', label: 'Datum' },
];

export type KeyOption = { value: Field; label: string };

/** An import without a key field creates every row instead of updating existing points. */
export const noKeyField: Field = 'ignore';

const idHeaderPattern = /^(id|uuid)$/i;

const uuidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

const headerGuesses: Array<[RegExp, Field]> = [
    [idHeaderPattern, 'id'],
    [/titel|bezeichnung|^name$/i, 'title'],
    [/beschreibung|description/i, 'description'],
    [/breitengrad|latitude|^lat$|y-?koordinate/i, 'lat'],
    [/längengrad|laengengrad|longitude|^lng$|^lon$|x-?koordinate/i, 'lng'],
    [/kategorie|category/i, 'category'],
    [/^ort|adresse|location/i, 'location'],
    [/veröffentlicht|published|öffentlich/i, 'published'],
];

/**
 * Suggests a field for each column from its header, so common spreadsheets need few changes. Every field
 * is suggested once at most.
 *
 * A column is only taken for the id when its values look like our ids. Lists written by hand often number
 * their rows in a column called "ID", and the import would then search for points that cannot exist.
 */
export function guessFields(headers: Array<string>, previewRows: PreviewRows): Array<Field> {
    const usedFields = new Set<Field>();
    const holdsSystemIds = (columnIndex: number) => previewRows.some((row) => uuidPattern.test(row[columnIndex] ?? ''));

    return headers.map((header, index) => {
        const guess = headerGuesses.find(
            ([pattern, field]) => pattern.test(header) && !usedFields.has(field) && (field !== 'id' || holdsSystemIds(index)),
        );

        if (!guess) {
            return 'ignore';
        }

        usedFields.add(guess[1]);
        return guess[1];
    });
}

const fieldPrefix = 'field:';
const characteristicPrefix = 'characteristic:';
const newFieldPrefix = 'new_field:';

export function targetOf(field: { value: Field; form_field_id: string | null; characteristic_id?: string | null }): ColumnTarget {
    if (field.value === 'characteristic' && field.characteristic_id) {
        return `${characteristicPrefix}${field.characteristic_id}`;
    }

    return field.form_field_id ? `${fieldPrefix}${field.form_field_id}` : field.value;
}

/** Characteristics and their fields are only offered for one main category, see availableFields(). */
export function belongsToCharacteristic(field: FieldOption): boolean {
    return field.characteristic_id !== null;
}

/**
 * The fields a column can be assigned to. Characteristics and their fields are only offered with an existing main
 * category, and only those it offers. Mixing them with several categories would be hard to understand.
 */
export function availableFields(
    fields: Array<FieldOption>,
    mainCategoryId: string | null,
    characteristicIdsByCategory: Record<string, Array<string>>,
): Array<FieldOption> {
    const offeredIds = mainCategoryId ? (characteristicIdsByCategory[mainCategoryId] ?? []) : [];

    return fields.filter((field) => !belongsToCharacteristic(field) || offeredIds.includes(field.characteristic_id!));
}

export function newFieldTarget(type: App.Enums.FieldType): ColumnTarget {
    return `${newFieldPrefix}${type}`;
}

export function columnFor(header: string, target: ColumnTarget): MapPointImportColumn {
    if (target.startsWith(fieldPrefix)) {
        return { header, field: 'field', form_field_id: target.slice(fieldPrefix.length) };
    }

    if (target.startsWith(characteristicPrefix)) {
        return { header, field: 'characteristic', characteristic_id: target.slice(characteristicPrefix.length) };
    }

    if (target.startsWith(newFieldPrefix)) {
        return { header, field: 'new_category_field', new_field_type: target.slice(newFieldPrefix.length) as App.Enums.FieldType };
    }

    return { header, field: target as Field };
}

/**
 * A new field would be created again on every import with the template, so templates leave such columns out.
 */
export function templateColumn(column: MapPointImportColumn): MapPointImportColumn {
    return column.field === 'new_category_field' ? { header: column.header, field: 'ignore' } : column;
}

/**
 * Suggests fields and characteristics for columns still unassigned, when the header is their label, with or without
 * its category as written by the export.
 */
export function guessCategoryFields(headers: Array<string>, targets: Array<ColumnTarget>, fields: Array<FieldOption>): Array<ColumnTarget> {
    const normalize = (text: string) => text.trim().toLowerCase();
    const categoryFields = fields.filter((field) => field.form_field_id || field.value === 'characteristic');
    const usedTargets = new Set<ColumnTarget>(targets);

    return targets.map((target, index) => {
        if (target !== 'ignore') {
            return target;
        }

        const header = normalize(headers[index] ?? '');
        const match = categoryFields.find(
            (field) =>
                !usedTargets.has(targetOf(field)) && (normalize(field.label) === header || normalize(field.label.split('›').pop() ?? '') === header),
        );

        if (!match) {
            return target;
        }

        usedTargets.add(targetOf(match));
        return targetOf(match);
    });
}

/** Matching by title keeps a repeated import from creating the same points twice. */
export function defaultKeyField(columnFields: Array<ColumnTarget>): Field {
    if (columnFields.includes('id')) {
        return 'id';
    }

    return columnFields.includes('title') ? 'title' : noKeyField;
}

/** Only assigned fields can recognise existing points. */
export function keyOptionsFor(fields: Array<FieldOption>, columnFields: Array<ColumnTarget>): Array<KeyOption> {
    const assignedKeyFields = fields
        .filter((field) => field.is_key && columnFields.includes(field.value))
        .map((field) => ({ value: field.value, label: field.label }));

    // An assigned id column would otherwise be read and dropped, which would copy every point a second time.
    if (columnFields.includes('id')) {
        return assignedKeyFields.filter((option) => option.value === 'id');
    }

    return [{ value: noKeyField, label: 'Gar nicht, jede Zeile wird neu angelegt' }, ...assignedKeyFields];
}

/** A column named like an id that is not assigned, because it holds a numbering of its own. */
export function unassignedIdHeader(headers: Array<string>, columnFields: Array<ColumnTarget>): string | null {
    if (columnFields.includes('id')) {
        return null;
    }

    return headers.find((header) => idHeaderPattern.test(header.trim())) ?? null;
}

export function sampleValues(previewRows: PreviewRows, columnIndex: number): string {
    return previewRows
        .map((row) => row[columnIndex])
        .filter((value): value is string => !!value)
        .slice(0, 2)
        .join(' · ');
}

/** Matches the headers of a saved template to the uploaded file, regardless of case and surrounding spaces. */
export function fieldsFromTemplate(
    headers: Array<string>,
    template: App.Data.MapPointSpreadsheetMappingData,
    fields: Array<FieldOption> = [],
): Array<ColumnTarget> {
    const normalize = (header: string) => header.trim().toLowerCase();
    const knownTargets = new Set(fields.map(targetOf));

    return headers.map((header) => {
        const column = template.columns.find((candidate) => normalize(candidate.header) === normalize(header));

        if (!column) {
            return 'ignore';
        }

        const target = targetOf({
            value: column.field,
            form_field_id: column.form_field_id ?? null,
            characteristic_id: column.characteristic_id ?? null,
        });

        // A template may name a field or characteristic that was deleted since, or that the main category does not offer.
        return (column.form_field_id || column.characteristic_id) && !knownTargets.has(target) ? 'ignore' : target;
    });
}

/**
 * Every part of the payload needs a comparison here. The mapped type turns a forgotten one into a type error,
 * so a new part cannot silently let an import run with a mapping that was never previewed.
 */
const payloadPartEquals: { [Part in keyof MapPointImportPayload]: (a: MapPointImportPayload, b: MapPointImportPayload) => boolean } = {
    token: (a, b) => a.token === b.token,
    key_field: (a, b) => a.key_field === b.key_field,
    default_published: (a, b) => a.default_published === b.default_published,
    columns: (a, b) =>
        a.columns.length === b.columns.length &&
        a.columns.every(
            (column, index) =>
                column.header === b.columns[index]?.header &&
                column.field === b.columns[index]?.field &&
                column.form_field_id === b.columns[index]?.form_field_id &&
                column.characteristic_id === b.columns[index]?.characteristic_id &&
                column.new_field_type === b.columns[index]?.new_field_type,
        ),
    main_category_id: (a, b) => a.main_category_id === b.main_category_id,
    new_main_category_name: (a, b) => a.new_main_category_name === b.new_main_category_name,
};

export function samePayload(a: MapPointImportPayload, b: MapPointImportPayload): boolean {
    return Object.values(payloadPartEquals).every((equals) => equals(a, b));
}

/**
 * The column mapping of one uploaded spreadsheet: which field every column goes into, how existing points
 * are recognised and whether new points are public.
 *
 * It lives exactly as long as the upload. The import page starts a new session for every file, so nothing
 * here has to be reset.
 */
export function useMapPointColumnMapping(
    upload: App.Data.SpreadsheetUploadData,
    fields: Array<FieldOption>,
    /** The ids of the fields each category has, keyed by category id. */
    categoryFieldIds: Record<string, Array<string>> = {},
    /** The ids of the characteristics points of each category can have, keyed by category id. */
    characteristicIdsByCategory: Record<string, Array<string>> = {},
) {
    const columnFields = ref<Array<ColumnTarget>>(
        guessCategoryFields(upload.headers, guessFields(upload.headers, upload.preview_rows), availableFields(fields, null, {})),
    );
    const keyField = ref<Field>(defaultKeyField(columnFields.value));
    const defaultVisibility = ref<DefaultVisibility>('private');
    const mainCategoryId = ref<string | null>(null);
    const newMainCategoryName = ref('');
    const createsMainCategory = ref(false);
    /** Once the main category was chosen by hand, it is no longer suggested. */
    const mainCategoryChosen = ref(false);

    /** What the columns can be assigned to with the chosen main category. */
    const offeredFields = computed(() =>
        availableFields(fields, createsMainCategory.value ? null : mainCategoryId.value, characteristicIdsByCategory),
    );

    const mainCategory = computed<MainCategorySelection>({
        get: () => (createsMainCategory.value ? 'new' : (mainCategoryId.value ?? 'none')),
        set: (selection) => {
            mainCategoryChosen.value = true;
            createsMainCategory.value = selection === 'new';
            mainCategoryId.value = selection === 'new' || selection === 'none' ? null : selection;

            // Characteristics another main category offered cannot be imported any more.
            const offeredTargets = new Set(offeredFields.value.map(targetOf));
            columnFields.value = columnFields.value.map((target) =>
                (target.startsWith(fieldPrefix) || target.startsWith(characteristicPrefix)) && !offeredTargets.has(target) ? 'ignore' : target,
            );

            if (mainCategoryId.value) {
                const ownFields = offeredFields.value.filter(
                    (field) =>
                        (field.form_field_id && (categoryFieldIds[mainCategoryId.value!] ?? []).includes(field.form_field_id)) ||
                        belongsToCharacteristic(field),
                );
                columnFields.value = guessCategoryFields(upload.headers, columnFields.value, ownFields);
            }
        },
    });

    const columns = computed<Array<MapPointImportColumn>>(() =>
        upload.headers.map((header, index) => columnFor(header, columnFields.value[index] ?? 'ignore')),
    );

    const payload = computed<MapPointImportPayload>(() => ({
        token: upload.token,
        key_field: keyField.value,
        default_published: defaultVisibility.value === 'public',
        columns: columns.value,
        main_category_id: createsMainCategory.value ? null : mainCategoryId.value,
        new_main_category_name: createsMainCategory.value ? newMainCategoryName.value.trim() || null : null,
    }));

    // Assigning a category field suggests its category as main category, unless one was chosen already.
    watch(
        columnFields,
        (current) => {
            if (mainCategoryChosen.value || mainCategoryId.value || createsMainCategory.value) {
                return;
            }

            const assignedField = offeredFields.value.find((field) => field.form_field_id && field.category_id && current.includes(targetOf(field)));
            mainCategoryId.value = assignedField?.category_id ?? null;
        },
        { deep: true, immediate: true },
    );

    // The key field must stay assigned, otherwise the import could not find the points to update.
    watch(
        columnFields,
        (current) => {
            const options = keyOptionsFor(fields, current);

            if (!options.some((option) => option.value === keyField.value)) {
                keyField.value = options[0]?.value ?? noKeyField;
            }
        },
        { deep: true },
    );

    function applyTemplate(template: App.Data.MapPointSpreadsheetMappingData): void {
        columnFields.value = fieldsFromTemplate(upload.headers, template, offeredFields.value);
        keyField.value = template.key_field;
    }

    return { columnFields, keyField, defaultVisibility, mainCategory, newMainCategoryName, offeredFields, columns, payload, applyTemplate };
}
