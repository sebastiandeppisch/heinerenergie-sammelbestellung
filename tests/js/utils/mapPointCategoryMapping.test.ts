import { sourceFieldCandidates, subcategoryCandidateIds } from '@/utils/mapPointCategoryMapping';
import { describe, expect, it } from 'vitest';
import { makeField } from '../fixtures/formDefinition';

const categories = [
    { id: 'pv', name: 'Photovoltaik', parent_id: null },
    { id: 'balcony', name: 'Balkonkraftwerke', parent_id: 'pv' },
    { id: 'with-storage', name: 'Mit Speicher', parent_id: 'balcony' },
    { id: 'storage', name: 'Speicher', parent_id: 'pv' },
    { id: 'heat-pumps', name: 'Wärmepumpen', parent_id: null },
];

const power = makeField({ id: 'power', type: 'number' });

describe('subcategoryCandidateIds', () => {
    it('offers the sub categories at any depth that use the fields of the category', () => {
        const fieldsByCategory = { pv: [power], balcony: [power], 'with-storage': [power], storage: [makeField({ id: 'capacity', type: 'number' })] };

        expect(subcategoryCandidateIds(categories, fieldsByCategory, 'pv')).toEqual(['balcony', 'with-storage']);
    });

    it('offers no category outside the tree of the category', () => {
        expect(subcategoryCandidateIds(categories, {}, 'balcony')).toEqual(['with-storage']);
    });
});

describe('sourceFieldCandidates', () => {
    const formFields = [
        makeField({ id: 'name', type: 'text' }),
        makeField({ id: 'notes', type: 'textarea' }),
        makeField({ id: 'kwp', type: 'number' }),
        makeField({ id: 'kind', type: 'radio' }),
    ];

    it('offers only form fields whose type can fill the category field', () => {
        const sourceTypes: Partial<Record<App.Enums.FieldType, Array<App.Enums.FieldType>>> = {
            text: ['text', 'textarea', 'email', 'phone'],
            select: ['select', 'radio'],
        };

        expect(sourceFieldCandidates(formFields, makeField({ id: 'contact', type: 'text' }), sourceTypes).map((field) => field.id)).toEqual([
            'name',
            'notes',
        ]);
        expect(sourceFieldCandidates(formFields, makeField({ id: 'roof', type: 'select' }), sourceTypes).map((field) => field.id)).toEqual(['kind']);
        expect(sourceFieldCandidates(formFields, makeField({ id: 'date', type: 'date' }), sourceTypes)).toEqual([]);
    });
});
