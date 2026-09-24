import ImportPreviewCard from '@/components/MapPointImport/ImportPreviewCard.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { answerNextPost, created, fakeInertia, resetFakeInertia } from '../../fixtures/inertia';
import { makePayload, makeResult, makeRow } from '../../fixtures/mapPointImport';

vi.mock('@inertiajs/vue3', async (importOriginal) => (await import('../../fixtures/inertia')).withFakeForms(await importOriginal()));
vi.mock('ziggy-js', async () => (await import('../../fixtures/inertia')).fakeZiggy);

function mountCard(payload = makePayload()) {
    const wrapper = mount(ImportPreviewCard, { props: { payload } });

    return { wrapper, previewRequest: created(fakeInertia.httpRequests), importForm: created(fakeInertia.forms) };
}

function importButton(wrapper: ReturnType<typeof mountCard>['wrapper']) {
    return wrapper.find('[data-test="run-import"]').element as HTMLButtonElement;
}

async function runPreview(wrapper: ReturnType<typeof mountCard>['wrapper']) {
    await wrapper.find('[data-test="run-preview"]').trigger('click');
    await flushPromises();
}

describe('ImportPreviewCard', () => {
    beforeEach(resetFakeInertia);

    it('cannot import before a preview was made', () => {
        const { wrapper } = mountCard();

        expect(importButton(wrapper).disabled).toBe(true);
    });

    it('previews with the current mapping and then allows the import', async () => {
        const payload = makePayload();
        const { wrapper, previewRequest } = mountCard(payload);
        answerNextPost(previewRequest, { response: makeResult() });

        await runPreview(wrapper);

        expect(previewRequest.post).toHaveBeenCalledWith('/mappoints.import.preview', expect.anything());
        expect(previewRequest.sentData()).toEqual(payload);
        expect(wrapper.find('[data-test="preview-counts"]').text()).toContain('1 neu');
        expect(wrapper.find('[data-test="preview-rows"]').text()).toContain('Balkonkraftwerk Musterweg 5');
        expect(importButton(wrapper).disabled).toBe(false);
    });

    it('asks for a new preview once the mapping changed', async () => {
        const { wrapper, previewRequest } = mountCard();
        answerNextPost(previewRequest, { response: makeResult() });
        await runPreview(wrapper);

        await wrapper.setProps({ payload: makePayload({ key_field: 'ignore' }) });

        expect(wrapper.find('[data-test="preview-outdated"]').exists()).toBe(true);
        expect(importButton(wrapper).disabled).toBe(true);
    });

    it('keeps the import closed while rows have errors', async () => {
        const { wrapper, previewRequest } = mountCard();
        answerNextPost(previewRequest, {
            response: makeResult({ rows: [], errors: [{ row: 3, column: 'Bezeichnung', message: 'Der Titel fehlt.' }] }),
        });

        await runPreview(wrapper);

        expect(wrapper.find('[data-test="row-errors"]').text()).toContain('Der Titel fehlt.');
        expect(importButton(wrapper).disabled).toBe(true);
    });

    it('shows why the server refused the mapping', async () => {
        const { wrapper, previewRequest } = mountCard();
        answerNextPost(previewRequest, { errors: { columns: 'Diese Felder müssen einer Spalte zugeordnet sein: Breitengrad.' } });

        await runPreview(wrapper);

        expect(wrapper.find('[data-test="request-error"]').text()).toBe('Diese Felder müssen einer Spalte zugeordnet sein: Breitengrad.');
        expect(wrapper.find('[data-test="preview-rows"]').exists()).toBe(false);
        expect(importButton(wrapper).disabled).toBe(true);
    });

    it('says so when the server could not answer at all', async () => {
        const { wrapper, previewRequest } = mountCard();
        answerNextPost(previewRequest, { failure: new Error('Request failed with status 500') });

        await runPreview(wrapper);

        expect(wrapper.find('[data-test="request-error"]').text()).toBe('Die Vorschau konnte nicht erstellt werden.');
    });

    it('does not leave an earlier preview usable when a later one fails', async () => {
        const { wrapper, previewRequest } = mountCard();
        answerNextPost(previewRequest, { response: makeResult() });
        await runPreview(wrapper);

        answerNextPost(previewRequest, { failure: new Error('Request failed with status 500') });
        await runPreview(wrapper);

        expect(wrapper.find('[data-test="preview-rows"]').exists()).toBe(false);
        expect(importButton(wrapper).disabled).toBe(true);
    });

    it('drops the messages of an earlier preview when previewing again', async () => {
        const { wrapper, previewRequest } = mountCard();
        answerNextPost(previewRequest, { errors: { columns: 'Diese Felder müssen einer Spalte zugeordnet sein: Breitengrad.' } });
        await runPreview(wrapper);

        answerNextPost(previewRequest, { response: makeResult({ rows: [makeRow({ is_update: true })] }) });
        await runPreview(wrapper);

        expect(wrapper.find('[data-test="request-error"]').exists()).toBe(false);
        expect(wrapper.find('[data-test="preview-counts"]').text()).toContain('1 aktualisiert');
    });

    it('imports with the previewed mapping and keeps the page state for a refused import', async () => {
        const payload = makePayload({ default_published: true });
        const { wrapper, previewRequest, importForm } = mountCard(payload);
        answerNextPost(previewRequest, { response: makeResult() });
        await runPreview(wrapper);

        await wrapper.find('[data-test="run-import"]').trigger('click');

        expect(importForm.post).toHaveBeenCalledWith('/mappoints.import.store', { preserveState: true, preserveScroll: true });
        expect(importForm.sentData()).toEqual(payload);
    });

    it('shows why the import was refused', async () => {
        const { wrapper, importForm } = mountCard();

        importForm.errors = { import: 'Die Datei enthält fehlerhafte Zeilen, deshalb wurde nichts importiert.' };
        await nextTick();

        expect(wrapper.find('[data-test="request-error"]').text()).toBe('Die Datei enthält fehlerhafte Zeilen, deshalb wurde nichts importiert.');
    });
});
