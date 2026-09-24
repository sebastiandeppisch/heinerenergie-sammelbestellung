import {
    defaultKeyField,
    fieldsFromTemplate,
    guessFields,
    keyOptionsFor,
    samePayload,
    sampleValues,
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
        const template = makeTemplate({ columns: [{ header: 'Bezeichnung', field: 'title' }] });

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

        mapping.applyTemplate(makeTemplate({ columns: [{ header: 'Bezeichnung', field: 'title' }], key_field: 'location' }));
        await nextTick();

        expect(mapping.keyField.value).toBe('ignore');
    });
});
