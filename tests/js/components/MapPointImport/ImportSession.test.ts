import ColumnMappingCard from '@/components/MapPointImport/ColumnMappingCard.vue';
import ColumnTemplateBar from '@/components/MapPointImport/ColumnTemplateBar.vue';
import ImportPreviewCard from '@/components/MapPointImport/ImportPreviewCard.vue';
import ImportSession from '@/components/MapPointImport/ImportSession.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { resetFakeInertia } from '../../fixtures/inertia';
import { guessedFields, makePayload, makeTemplate, makeUpload, mapPointFields } from '../../fixtures/mapPointImport';

vi.mock('@inertiajs/vue3', async (importOriginal) => (await import('../../fixtures/inertia')).withFakeForms(await importOriginal()));
vi.mock('ziggy-js', async () => (await import('../../fixtures/inertia')).fakeZiggy);

function mountSession() {
    return mount(ImportSession, {
        props: { upload: makeUpload(), fields: mapPointFields, mappings: [makeTemplate()] },
    });
}

function previewPayload(wrapper: ReturnType<typeof mountSession>) {
    return wrapper.findComponent(ImportPreviewCard).props('payload');
}

describe('ImportSession', () => {
    beforeEach(resetFakeInertia);

    it('previews and imports with the guessed mapping of a new file', () => {
        expect(previewPayload(mountSession())).toEqual(makePayload());
    });

    it('previews with a column changed in the mapping card', async () => {
        const wrapper = mountSession();

        wrapper.findComponent(ColumnMappingCard).vm.$emit(
            'update:columnFields',
            guessedFields.map((field, index) => (index === 3 ? 'category' : field)),
        );
        await nextTick();

        expect(previewPayload(wrapper).columns[3]).toEqual({ header: 'Art der Anlage', field: 'category' });
    });

    it('previews with a template chosen in the template bar', async () => {
        const wrapper = mountSession();

        wrapper.findComponent(ColumnTemplateBar).vm.$emit('apply', makeTemplate());
        await nextTick();

        expect(previewPayload(wrapper).key_field).toBe('location');
        expect(previewPayload(wrapper).columns.map((column) => column.field)).toEqual([
            'ignore',
            'title',
            'ignore',
            'category',
            'location',
            'lat',
            'lng',
        ]);
    });

    it('saves templates with the mapping as it currently is', async () => {
        const wrapper = mountSession();

        wrapper.findComponent(ColumnMappingCard).vm.$emit('update:keyField', 'location');
        await nextTick();

        expect(wrapper.findComponent(ColumnTemplateBar).props('keyField')).toBe('location');
        expect(wrapper.findComponent(ColumnTemplateBar).props('columns')).toEqual(makePayload().columns);
    });
});
