import UploadCard from '@/components/MapPointImport/UploadCard.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { created, fakeInertia, resetFakeInertia } from '../../fixtures/inertia';
import { makeUpload } from '../../fixtures/mapPointImport';

vi.mock('@inertiajs/vue3', async (importOriginal) => (await import('../../fixtures/inertia')).withFakeForms(await importOriginal()));
vi.mock('ziggy-js', async () => (await import('../../fixtures/inertia')).fakeZiggy);

describe('UploadCard', () => {
    beforeEach(resetFakeInertia);

    it('uploads a chosen file as form data', async () => {
        const wrapper = mount(UploadCard, { props: { upload: null } });
        const form = created(fakeInertia.forms);
        const file = new File(['Titel;Breitengrad;Längengrad'], 'anlagen.csv', { type: 'text/csv' });
        const input = wrapper.find('input[type="file"]');

        Object.defineProperty(input.element, 'files', { value: [file] });
        await input.trigger('change');

        expect(form.file).toBe(file);
        expect(form.post).toHaveBeenCalledWith('/mappoints.import.upload', { forceFormData: true });
    });

    it('names the uploaded file and how many rows it has', () => {
        const wrapper = mount(UploadCard, { props: { upload: makeUpload() } });

        expect(wrapper.text()).toContain('anlagen.csv · 2 Zeilen');
        expect(wrapper.text()).toContain('Andere Datei wählen');
    });

    it('shows why a file was refused', async () => {
        const wrapper = mount(UploadCard, { props: { upload: null } });

        created(fakeInertia.forms).errors = { file: 'Jede Spaltenüberschrift darf nur einmal vorkommen.' };
        await nextTick();

        expect(wrapper.find('[data-test="upload-error"]').text()).toBe('Jede Spaltenüberschrift darf nur einmal vorkommen.');
    });
});
