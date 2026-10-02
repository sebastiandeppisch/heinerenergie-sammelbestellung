import {
    availableFields,
    columnFor,
    defaultKeyField,
    fieldsFromTemplate,
    guessCategoryFields,
    guessFields,
    keyOptionsFor,
    newFieldTarget,
    samePayload,
    sampleValues,
    templateColumn,
    unassignedIdHeader,
    useMapPointColumnMapping,
} from '@/composables/useMapPointColumnMapping';
import { describe, expect, it } from 'vitest';
import { effectScope, nextTick } from 'vue';
import { exportedId, guessedFields, makePayload, makeTemplate, makeUpload, mapPointFields } from '../fixtures/mapPointImport';

function startMapping(upload = makeUpload()) {
    const mapping = effectScope().run(() => useMapPointColumnMapping(upload, mapPointFields));

    if (!mapping) {
        throw new Error('The column mapping could not be started.');
    }

    return mapping;
}

describe('guessFields', () => {
    it('suggests fields from the headers of a list kept in German', () => {
        const upload = makeUpload();

        expect(guessFields(upload.headers, upload.preview_rows)).toEqual(guessedFields);
    });

    it('takes a column called ID only when it holds ids exported by this application', () => {
        const headers = ['ID', 'Titel'];

        expect(guessFields(headers, [['17', 'Balkonkraftwerk Musterweg 5']])).toEqual(['ignore', 'title']);
        expect(guessFields(headers, [[exportedId, 'Balkonkraftwerk Musterweg 5']])).toEqual(['id', 'title']);
    });

    it('suggests every field for one column only', () => {
        expect(guessFields(['Titel', 'Bezeichnung'], [])).toEqual(['title', 'ignore']);
    });
});

describe('defaultKeyField', () => {
    it('prefers the id, then the title, and otherwise creates every row', () => {
        expect(defaultKeyField(['id', 'title'])).toBe('id');
        expect(defaultKeyField(['title', 'lat'])).toBe('title');
        expect(defaultKeyField(['lat', 'lng'])).toBe('ignore');
    });
});

describe('keyOptionsFor', () => {
    it('offers creating every row and each assigned field that can recognise points', () => {
        const options = keyOptionsFor(mapPointFields, ['title', 'location', 'lat', 'lng']);

        expect(options.map((option) => option.value)).toEqual(['ignore', 'title', 'location']);
    });

    it('offers only the id once an id column is assigned, so its values are never dropped', () => {
        const options = keyOptionsFor(mapPointFields, ['id', 'title', 'location']);

        expect(options.map((option) => option.value)).toEqual(['id']);
    });
});

describe('unassignedIdHeader', () => {
    it('names a column called ID that holds a numbering of its own', () => {
        expect(unassignedIdHeader(['ID', 'Titel'], ['ignore', 'title'])).toBe('ID');
    });

    it('stays quiet when the id column is assigned or there is none', () => {
        expect(unassignedIdHeader(['ID', 'Titel'], ['id', 'title'])).toBeNull();
        expect(unassignedIdHeader(['Nr', 'Titel'], ['ignore', 'title'])).toBeNull();
    });
});

describe('sampleValues', () => {
    it('shows the first two values of a column and skips empty cells', () => {
        expect(sampleValues([['Photovoltaik'], [null], ['Wärmepumpe'], ['Solarthermie']], 0)).toBe('Photovoltaik · Wärmepumpe');
    });
});

describe('fieldsFromTemplate', () => {
    it('matches the headers of a template regardless of case and surrounding spaces', () => {
        const template = makeTemplate({
            columns: [{ header: 'Bezeichnung', field: 'title', form_field_id: null, new_field_type: null, characteristic_id: null }],
        });

        expect(fieldsFromTemplate([' bezeichnung ', 'Leistung'], template)).toEqual(['title', 'ignore']);
    });
});

describe('samePayload', () => {
    it('treats payloads with the same content as the same, whatever order their keys were written in', () => {
        const payload = makePayload();
        const sameContent = {
            columns: payload.columns.map((column) => ({ ...column })),
            default_published: false,
            key_field: payload.key_field,
            main_category_id: null,
            new_main_category_name: null,
            token: payload.token,
        };

        expect(samePayload(payload, sameContent)).toBe(true);
    });

    it('tells apart every part of the payload', () => {
        const payload = makePayload();
        const [first, second, ...rest] = payload.columns;

        expect(samePayload(payload, { ...payload, token: 'another-upload' })).toBe(false);
        expect(samePayload(payload, { ...payload, key_field: 'location' })).toBe(false);
        expect(samePayload(payload, { ...payload, default_published: true })).toBe(false);
        expect(samePayload(payload, { ...payload, columns: payload.columns.slice(1) })).toBe(false);
        expect(
            samePayload(payload, {
                ...payload,
                columns: payload.columns.map((column, index) => (index === 3 ? { ...column, field: 'category' } : column)),
            }),
        ).toBe(false);

        if (first && second) {
            expect(samePayload(payload, { ...payload, columns: [second, first, ...rest] })).toBe(false);
        }
    });
});

describe('useMapPointColumnMapping', () => {
    it('starts with the guessed fields and recognises existing points by their title', () => {
        const mapping = startMapping();

        expect(mapping.columnFields.value).toEqual(guessedFields);
        expect(mapping.payload.value).toEqual(makePayload());
    });

    it('sends that new points are published once that is chosen', () => {
        const mapping = startMapping();

        mapping.defaultVisibility.value = 'public';

        expect(mapping.payload.value.default_published).toBe(true);
    });

    it('creates every row once the column of the key field is no longer assigned', async () => {
        const mapping = startMapping();

        mapping.columnFields.value = guessedFields.map((field) => (field === 'title' ? 'ignore' : field));
        await nextTick();

        expect(mapping.keyField.value).toBe('ignore');
    });

    it('recognises points by the id as soon as an id column is assigned', async () => {
        const mapping = startMapping();

        mapping.columnFields.value = guessedFields.map((field, index) => (index === 0 ? 'id' : field));
        await nextTick();

        expect(mapping.keyField.value).toBe('id');
    });

    it('takes over the columns and the key field of a saved template', async () => {
        const mapping = startMapping();

        mapping.applyTemplate(makeTemplate());
        await nextTick();

        expect(mapping.columnFields.value).toEqual(['ignore', 'title', 'ignore', 'category', 'location', 'lat', 'lng']);
        expect(mapping.keyField.value).toBe('location');
    });

    it('does not keep a key field from a template whose column this file lacks', async () => {
        const mapping = startMapping();

        mapping.applyTemplate(
            makeTemplate({
                columns: [{ header: 'Bezeichnung', field: 'title', form_field_id: null, new_field_type: null, characteristic_id: null }],
                key_field: 'location',
            }),
        );
        await nextTick();

        expect(mapping.keyField.value).toBe('ignore');
    });
});

describe('category field columns', () => {
    const powerId = '9a1b2c3d-4e5f-4a6b-8c7d-0e1f2a3b4c5d';
    const categoryFields: Array<App.Data.MapPointSpreadsheetFieldData> = [
        {
            value: 'field',
            label: 'Photovoltaik › PV-Leistung (kWp)',
            is_key: false,
            form_field_id: powerId,
            category_id: 'pv',
            characteristic_id: null,
        },
    ];

    it('suggests a category field for a column named like the field, with or without its category', () => {
        const fields = [...mapPointFields, ...categoryFields];

        expect(guessCategoryFields(['Bezeichnung', 'PV-Leistung (kWp)'], ['title', 'ignore'], fields)).toEqual(['title', `field:${powerId}`]);
        expect(guessCategoryFields(['Photovoltaik › PV-Leistung (kWp)'], ['ignore'], fields)).toEqual([`field:${powerId}`]);
    });

    it('sends a category field column with the id of its field', () => {
        expect(columnFor('Leistung', `field:${powerId}`)).toEqual({
            header: 'Leistung',
            field: 'field',
            form_field_id: powerId,
        });
        expect(columnFor('Bezeichnung', 'title')).toEqual({ header: 'Bezeichnung', field: 'title' });
    });

    it('ignores a template column whose category field no longer exists', () => {
        const template = makeTemplate({
            columns: [
                { header: 'Leistung', field: 'field', form_field_id: powerId, new_field_type: null, characteristic_id: null },
                { header: 'Alt', field: 'field', form_field_id: 'deleted-field', new_field_type: null, characteristic_id: null },
            ],
        });

        expect(fieldsFromTemplate(['Leistung', 'Alt'], template, [...mapPointFields, ...categoryFields])).toEqual([`field:${powerId}`, 'ignore']);
    });
});

describe('main category', () => {
    const powerId = '9a1b2c3d-4e5f-4a6b-8c7d-0e1f2a3b4c5d';
    const power: App.Data.MapPointSpreadsheetFieldData = {
        value: 'field',
        label: 'Photovoltaik › Leistung',
        is_key: false,
        form_field_id: powerId,
        category_id: 'pv',
        characteristic_id: null,
    };
    const upload = makeUpload({ headers: ['Bezeichnung', 'Leistung', 'Hersteller'], preview_rows: [['Schule', '9,9', 'Muster AG']] });

    function startMainCategoryMapping() {
        const mapping = effectScope().run(() => useMapPointColumnMapping(upload, [...mapPointFields, power], { pv: [powerId], balcony: [powerId] }));

        if (!mapping) {
            throw new Error('The column mapping could not be started.');
        }

        return mapping;
    }

    it('assigns the fields of the chosen main category to columns named like them', () => {
        const mapping = startMainCategoryMapping();

        mapping.mainCategory.value = 'balcony';

        expect(mapping.columnFields.value[1]).toBe(`field:${powerId}`);
        expect(mapping.payload.value.main_category_id).toBe('balcony');
    });

    it('suggests the category of an assigned field as main category until one is chosen', async () => {
        const mapping = startMainCategoryMapping();

        expect(mapping.mainCategory.value).toBe('pv');

        mapping.mainCategory.value = 'none';
        mapping.columnFields.value = ['title', `field:${powerId}`, 'ignore'];
        await nextTick();

        expect(mapping.mainCategory.value).toBe('none');
    });

    it('sends the name of a new main category instead of an id', () => {
        const mapping = startMainCategoryMapping();

        mapping.mainCategory.value = 'new';
        mapping.newMainCategoryName.value = '  Photovoltaik ';

        expect(mapping.payload.value).toMatchObject({ main_category_id: null, new_main_category_name: 'Photovoltaik' });
    });

    it('sends a new field with its type and leaves it out of templates', () => {
        const column = columnFor('Hersteller', newFieldTarget('text'));

        expect(column).toEqual({ header: 'Hersteller', field: 'new_category_field', new_field_type: 'text' });
        expect(templateColumn(column)).toEqual({ header: 'Hersteller', field: 'ignore' });
    });
});

describe('characteristic columns', () => {
    const hedgehogGateId = '5c2d7e8f-1a2b-4c3d-9e8f-7a6b5c4d3e2f';
    const widthId = '6d3e8f9a-2b3c-4d4e-8f9a-8b7c6d5e4f3a';
    const characteristicFields: Array<App.Data.MapPointSpreadsheetFieldData> = [
        {
            value: 'characteristic',
            label: 'Garten › Maßnahme Igeltor',
            is_key: false,
            form_field_id: null,
            category_id: 'garden',
            characteristic_id: hedgehogGateId,
        },
        {
            value: 'field',
            label: 'Garten › Igeltor › Breite (cm)',
            is_key: false,
            form_field_id: widthId,
            category_id: 'garden',
            characteristic_id: hedgehogGateId,
        },
    ];
    const upload = makeUpload({ headers: ['Bezeichnung', 'Garten › Maßnahme Igeltor', 'Breite (cm)'], preview_rows: [['Schulgarten', 'x', '13']] });

    function startCharacteristicMapping() {
        const mapping = effectScope().run(() =>
            useMapPointColumnMapping(upload, [...mapPointFields, ...characteristicFields], {}, { garden: [hedgehogGateId], solar: [] }),
        );

        if (!mapping) {
            throw new Error('The column mapping could not be started.');
        }

        return mapping;
    }

    it('offers characteristics and their fields only for a main category that offers them', () => {
        const fields = [...mapPointFields, ...characteristicFields];

        expect(availableFields(fields, null, { garden: [hedgehogGateId] })).toEqual(mapPointFields);
        expect(availableFields(fields, 'solar', { garden: [hedgehogGateId], solar: [] })).toEqual(mapPointFields);
        expect(availableFields(fields, 'garden', { garden: [hedgehogGateId] })).toEqual(fields);
    });

    it('assigns the characteristics of the chosen main category to columns named like them and sends their ids', () => {
        const mapping = startCharacteristicMapping();

        expect(mapping.columnFields.value).toEqual(['title', 'ignore', 'ignore']);

        mapping.mainCategory.value = 'garden';

        expect(mapping.columnFields.value).toEqual(['title', `characteristic:${hedgehogGateId}`, `field:${widthId}`]);
        expect(mapping.payload.value.columns[1]).toEqual({
            header: 'Garten › Maßnahme Igeltor',
            field: 'characteristic',
            characteristic_id: hedgehogGateId,
        });
    });

    it('unassigns characteristic columns when the main category no longer offers them', () => {
        const mapping = startCharacteristicMapping();
        mapping.mainCategory.value = 'garden';

        mapping.mainCategory.value = 'none';

        expect(mapping.columnFields.value).toEqual(['title', 'ignore', 'ignore']);
    });

    it('tells payloads apart by the characteristic of a column', () => {
        const payload = makePayload({ columns: [{ header: 'Igeltor', field: 'characteristic', characteristic_id: hedgehogGateId }] });

        expect(samePayload(payload, { ...payload, columns: [{ header: 'Igeltor', field: 'characteristic', characteristic_id: 'another' }] })).toBe(
            false,
        );
    });
});
