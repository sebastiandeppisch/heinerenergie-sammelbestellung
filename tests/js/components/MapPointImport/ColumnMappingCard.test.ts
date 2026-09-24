import ColumnMappingCard from '@/components/MapPointImport/ColumnMappingCard.vue';
import { Select } from '@/shadcn/components/ui/select';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { exportedId, guessedFields, makeUpload, mapPointFields } from '../../fixtures/mapPointImport';

type Field = App.Enums.MapPointSpreadsheetField;

function mountCard(columnFields: Array<Field> = guessedFields, upload = makeUpload()) {
    return mount(ColumnMappingCard, {
        props: {
            upload,
            fields: mapPointFields,
            columnFields,
            keyField: 'title',
            defaultVisibility: 'private',
        },
        slots: {
            templates: '<div data-test="templates-slot">Vorlagen</div>',
        },
    });
}

function columnSelect(wrapper: ReturnType<typeof mountCard>, columnIndex: number) {
    const row = wrapper.findAll('[data-test="column-row"]')[columnIndex];

    if (!row) {
        throw new Error(`There is no row for column ${columnIndex}.`);
    }

    return row.findComponent(Select);
}

describe('ColumnMappingCard', () => {
    it('hands a changed column up as a new list and leaves the given one alone', async () => {
        const columnFields = [...guessedFields];
        const wrapper = mountCard(columnFields);

        columnSelect(wrapper, 3).vm.$emit('update:modelValue', 'category');

        expect(wrapper.emitted('update:columnFields')?.[0]).toEqual([['ignore', 'title', 'description', 'category', 'location', 'lat', 'lng']]);
        expect(columnFields).toEqual(guessedFields);
    });

    it('explains that a column called ID with its own numbering is not taken for the id', () => {
        const upload = makeUpload({ headers: ['ID', 'Bezeichnung'], preview_rows: [['17', 'Balkonkraftwerk Musterweg 5']] });
        const wrapper = mountCard(['ignore', 'title'], upload);

        expect(wrapper.find('[data-test="id-unassigned-hint"]').text()).toContain('„ID“');
        expect(wrapper.find('[data-test="id-assigned-hint"]').exists()).toBe(false);
    });

    it('explains that points are recognised by the id once an id column is assigned', () => {
        const upload = makeUpload({ headers: ['ID', 'Bezeichnung'], preview_rows: [[exportedId, 'Balkonkraftwerk Musterweg 5']] });
        const wrapper = mountCard(['id', 'title'], upload);

        expect(wrapper.find('[data-test="id-assigned-hint"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="id-unassigned-hint"]').exists()).toBe(false);
    });

    it('leaves the visibility to a column assigned to it', () => {
        const wrapper = mountCard([...guessedFields.slice(0, 3), 'published', ...guessedFields.slice(4)]);
        const visibilitySelect = wrapper.findAllComponents(Select)[1];

        expect(visibilitySelect?.props('disabled')).toBe(true);
        expect(wrapper.find('[data-test="visibility-hint"]').text()).toContain('entscheidet je Zeile');
    });

    it('lets the user choose the visibility of new points without such a column', () => {
        const wrapper = mountCard();
        const visibilitySelect = wrapper.findAllComponents(Select)[1];

        expect(visibilitySelect?.props('disabled')).toBe(false);
        expect(wrapper.find('[data-test="visibility-hint"]').text()).toContain('Vorhandene Punkte behalten ihren Stand');
    });

    it('shows the templates above the mapping', () => {
        expect(mountCard().find('[data-test="templates-slot"]').exists()).toBe(true);
    });
});
