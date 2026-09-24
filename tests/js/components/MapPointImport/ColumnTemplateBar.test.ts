import ColumnTemplateBar from '@/components/MapPointImport/ColumnTemplateBar.vue';
import { Select } from '@/shadcn/components/ui/select';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { created, fakeInertia, resetFakeInertia } from '../../fixtures/inertia';
import { makePayload, makeTemplate } from '../../fixtures/mapPointImport';

vi.mock('@inertiajs/vue3', async (importOriginal) => (await import('../../fixtures/inertia')).withFakeForms(await importOriginal()));
vi.mock('ziggy-js', async () => (await import('../../fixtures/inertia')).fakeZiggy);

const template = makeTemplate();
const { columns } = makePayload();

function mountBar() {
    return mount(ColumnTemplateBar, {
        props: { mappings: [template], columns, keyField: 'title' },
    });
}

async function chooseTemplate(wrapper: ReturnType<typeof mountBar>) {
    wrapper.findComponent(Select).vm.$emit('update:modelValue', template.id);
    await nextTick();
}

describe('ColumnTemplateBar', () => {
    beforeEach(resetFakeInertia);

    it('applies a chosen template and takes over its name', async () => {
        const wrapper = mountBar();

        await chooseTemplate(wrapper);

        expect(wrapper.emitted('apply')?.[0]).toEqual([template]);
        expect((wrapper.find('[data-test="template-name"]').element as HTMLInputElement).value).toBe(template.name);
    });

    it('cannot save a template without a name', () => {
        const wrapper = mountBar();

        expect((wrapper.find('[data-test="save-template"]').element as HTMLButtonElement).disabled).toBe(true);
    });

    it('saves the current mapping as a new template', async () => {
        const wrapper = mountBar();
        const form = created(fakeInertia.forms);

        await wrapper.find('[data-test="template-name"]').setValue('Wärmepumpen');
        await wrapper.find('[data-test="save-template"]').trigger('click');

        expect(form.post).toHaveBeenCalledWith('/mappoints.spreadsheet-mappings.store', expect.objectContaining({ preserveState: true }));
        expect(form.sentData()).toEqual({ name: 'Wärmepumpen', key_field: 'title', columns });
    });

    it('updates the chosen template', async () => {
        const wrapper = mountBar();
        const form = created(fakeInertia.forms);

        await chooseTemplate(wrapper);
        await wrapper.find('[data-test="update-template"]').trigger('click');

        expect(form.put).toHaveBeenCalledWith(
            `/mappoints.spreadsheet-mappings.update/${template.id}`,
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('deletes the chosen template only after confirmation', async () => {
        const wrapper = mountBar();
        const form = created(fakeInertia.forms);
        const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

        await chooseTemplate(wrapper);
        await wrapper.find('[data-test="delete-template"]').trigger('click');
        expect(form.delete).not.toHaveBeenCalled();

        confirm.mockReturnValue(true);
        await wrapper.find('[data-test="delete-template"]').trigger('click');
        expect(form.delete).toHaveBeenCalledWith(
            `/mappoints.spreadsheet-mappings.destroy/${template.id}`,
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('shows why a template could not be saved', async () => {
        const wrapper = mountBar();

        created(fakeInertia.forms).errors = { name: 'Name ist bereits vergeben.' };
        await nextTick();

        expect(wrapper.find('[data-test="template-error"]').text()).toBe('Name ist bereits vergeben.');
    });
});
