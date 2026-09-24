import { computed, ref, watch } from 'vue';

type Field = App.Enums.MapPointSpreadsheetField;
type FieldOption = App.Data.MapPointSpreadsheetFieldData;
type PreviewRows = Array<Array<string | null>>;

export type DefaultVisibility = 'private' | 'public';

export type MapPointImportColumn = { header: string; field: Field };

export type MapPointImportPayload = {
    token: string;
    key_field: Field;
    default_published: boolean;
    columns: Array<MapPointImportColumn>;
};

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

/** Matching by title keeps a repeated import from creating the same points twice. */
export function defaultKeyField(columnFields: Array<Field>): Field {
    if (columnFields.includes('id')) {
        return 'id';
    }

    return columnFields.includes('title') ? 'title' : noKeyField;
}

/** Only assigned fields can recognise existing points. */
export function keyOptionsFor(fields: Array<FieldOption>, columnFields: Array<Field>): Array<KeyOption> {
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
export function unassignedIdHeader(headers: Array<string>, columnFields: Array<Field>): string | null {
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
export function fieldsFromTemplate(headers: Array<string>, template: App.Data.MapPointSpreadsheetMappingData): Array<Field> {
    const normalize = (header: string) => header.trim().toLowerCase();

    return headers.map((header) => template.columns.find((column) => normalize(column.header) === normalize(header))?.field ?? 'ignore');
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
        a.columns.every((column, index) => column.header === b.columns[index]?.header && column.field === b.columns[index]?.field),
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
export function useMapPointColumnMapping(upload: App.Data.SpreadsheetUploadData, fields: Array<FieldOption>) {
    const columnFields = ref<Array<Field>>(guessFields(upload.headers, upload.preview_rows));
    const keyField = ref<Field>(defaultKeyField(columnFields.value));
    const defaultVisibility = ref<DefaultVisibility>('private');

    const columns = computed<Array<MapPointImportColumn>>(() =>
        upload.headers.map((header, index) => ({ header, field: columnFields.value[index] ?? 'ignore' })),
    );

    const payload = computed<MapPointImportPayload>(() => ({
        token: upload.token,
        key_field: keyField.value,
        default_published: defaultVisibility.value === 'public',
        columns: columns.value,
    }));

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
        columnFields.value = fieldsFromTemplate(upload.headers, template);
        keyField.value = template.key_field;
    }

    return { columnFields, keyField, defaultVisibility, columns, payload, applyTemplate };
}
