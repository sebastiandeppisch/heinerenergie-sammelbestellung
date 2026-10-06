import { clearBrokenConditions, dependentFieldsByOption, isFieldVisible, topLevelFields, withoutHiddenValues } from '@/utils/formFieldVisibility';
import { describe, expect, it } from 'vitest';
import { makeField } from '../fixtures/formDefinition';

function option(value: string) {
    return { id: value, label: value, value, sort_order: 0, is_default: false, is_required: false };
}

const characteristics = makeField({ id: 'characteristics', type: 'checkbox', options: [option('gate'), option('deadwood')] });
const gateWidth = makeField({ id: 'gate-width', type: 'number', visible_if_field_id: 'characteristics', visible_if_option_value: 'gate' });
const gateMaterial = makeField({
    id: 'gate-material',
    type: 'select',
    options: [option('wood'), option('metal')],
    visible_if_field_id: 'characteristics',
    visible_if_option_value: 'gate',
});
const metalNote = makeField({ id: 'metal-note', type: 'text', visible_if_field_id: 'gate-material', visible_if_option_value: 'metal' });
const fields = [characteristics, gateWidth, gateMaterial, metalNote];
const fieldsById = new Map(fields.map((field) => [field.id, field]));

describe('isFieldVisible', () => {
    it('shows a field depending on a checkbox only while its option is checked', () => {
        expect(isFieldVisible(gateWidth, { characteristics: ['gate'] }, fieldsById)).toBe(true);
        expect(isFieldVisible(gateWidth, { characteristics: ['deadwood'] }, fieldsById)).toBe(false);
    });

    it('shows a field depending on a select only for the chosen option', () => {
        expect(isFieldVisible(metalNote, { characteristics: ['gate'], 'gate-material': 'metal' }, fieldsById)).toBe(true);
        expect(isFieldVisible(metalNote, { characteristics: ['gate'], 'gate-material': 'wood' }, fieldsById)).toBe(false);
    });

    it('hides a field whose condition field is hidden itself', () => {
        expect(isFieldVisible(metalNote, { characteristics: [], 'gate-material': 'metal' }, fieldsById)).toBe(false);
    });

    it('ignores a condition on a field that no longer exists', () => {
        expect(isFieldVisible(gateWidth, {}, new Map())).toBe(true);
    });
});

describe('field layout', () => {
    it('shows only fields without condition on their own and the others below their option', () => {
        expect(topLevelFields(fields)).toEqual([characteristics]);

        const dependents = dependentFieldsByOption(fields);
        expect(dependents.get('characteristics')?.get('gate')).toEqual([gateWidth, gateMaterial]);
        expect(dependents.get('gate-material')?.get('metal')).toEqual([metalNote]);
    });
});

describe('withoutHiddenValues', () => {
    it('empties the values of hidden fields', () => {
        const values = { characteristics: ['deadwood'], 'gate-width': 13, 'gate-material': 'metal', 'metal-note': 'verzinkt' };

        expect(withoutHiddenValues(fields, values)).toEqual({
            characteristics: ['deadwood'],
            'gate-width': null,
            'gate-material': null,
            'metal-note': null,
        });
    });
});

describe('clearBrokenConditions', () => {
    it('removes conditions on deleted fields and options', () => {
        const width = makeField({ id: 'width', type: 'number', visible_if_field_id: 'characteristics', visible_if_option_value: 'removed' });
        const note = makeField({ id: 'note', type: 'text', visible_if_field_id: 'deleted', visible_if_option_value: 'gate' });
        const kept = { ...gateWidth };

        clearBrokenConditions([characteristics, width, note, kept]);

        expect([width.visible_if_field_id, note.visible_if_field_id, kept.visible_if_field_id]).toEqual([null, null, 'characteristics']);
    });
});
