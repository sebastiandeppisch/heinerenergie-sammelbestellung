import type { MapPointImportPayload } from '@/composables/useMapPointColumnMapping';

/** The field options as the server sends them. */
export const mapPointFields: Array<App.Data.MapPointSpreadsheetFieldData> = [
    { value: 'ignore', label: 'Ignorieren', is_key: false },
    { value: 'id', label: 'ID', is_key: true },
    { value: 'title', label: 'Titel', is_key: true },
    { value: 'description', label: 'Beschreibung', is_key: false },
    { value: 'lat', label: 'Breitengrad', is_key: false },
    { value: 'lng', label: 'Längengrad', is_key: false },
    { value: 'location', label: 'Ort', is_key: true },
    { value: 'category', label: 'Kategorie', is_key: false },
    { value: 'published', label: 'Veröffentlicht', is_key: false },
];

export const exportedId = '3f1a5d4e-0c62-4a5f-9f1e-6d2b8c7a4e55';

/** A list of installations as an initiative keeps it: its own numbering, German headers, decimal commas. */
export function makeUpload(overrides: Partial<App.Data.SpreadsheetUploadData> = {}): App.Data.SpreadsheetUploadData {
    return {
        token: '7b0b4a1e-2f7d-4a52-9b8e-1c2d3e4f5a6b',
        filename: 'anlagen.csv',
        headers: ['Nr', 'Bezeichnung', 'kurze Beschreibung', 'Art der Anlage', 'Ortsbezeichnung', 'Y-Koordinate', 'X-Koordinate'],
        preview_rows: [
            ['4', 'Balkonkraftwerk Musterweg 5', 'Steckersolargerät mit 800 Watt', 'Photovoltaik', 'Musterstadt Nord', '49,123456', '8,654321'],
            ['5', 'Wärmepumpe Beispielhof', 'Luft-Wasser-Wärmepumpe', 'Wärmepumpe', 'Musterstadt Süd', '49,234567', '8,765432'],
        ],
        row_count: 2,
        ...overrides,
    };
}

/** The fields guessed for the headers of makeUpload(). */
export const guessedFields: Array<App.Enums.MapPointSpreadsheetField> = ['ignore', 'title', 'description', 'ignore', 'location', 'lat', 'lng'];

export function makeTemplate(overrides: Partial<App.Data.MapPointSpreadsheetMappingData> = {}): App.Data.MapPointSpreadsheetMappingData {
    return {
        id: 'b2c6f0a4-5d1e-4f3a-8c7b-9e0d1a2b3c4d',
        name: 'Anlagenliste',
        key_field: 'location',
        columns: [
            { header: 'Bezeichnung', field: 'title' },
            { header: 'Art der Anlage', field: 'category' },
            { header: 'Ortsbezeichnung', field: 'location' },
            { header: 'Y-Koordinate', field: 'lat' },
            { header: 'X-Koordinate', field: 'lng' },
        ],
        ...overrides,
    };
}

export function makePayload(overrides: Partial<MapPointImportPayload> = {}): MapPointImportPayload {
    const upload = makeUpload();

    return {
        token: upload.token,
        key_field: 'title',
        default_published: false,
        columns: upload.headers.map((header, index) => ({ header, field: guessedFields[index] ?? 'ignore' })),
        ...overrides,
    };
}

export function makeRow(overrides: Partial<App.Data.MapPointImportRowData> = {}): App.Data.MapPointImportRowData {
    return {
        row: 2,
        is_update: false,
        title: 'Balkonkraftwerk Musterweg 5',
        lat: 49.123456,
        lng: 8.654321,
        location: 'Musterstadt Nord',
        category: 'Photovoltaik',
        published: false,
        group_name: 'Musterinitiative',
        ...overrides,
    };
}

export function makeResult(overrides: Partial<App.Data.MapPointImportResultData> = {}): App.Data.MapPointImportResultData {
    const rows = overrides.rows ?? [makeRow()];

    return {
        created_count: rows.filter((row) => !row.is_update).length,
        updated_count: rows.filter((row) => row.is_update).length,
        rows,
        created_categories: [],
        errors: [],
        ...overrides,
    };
}
