import FormTargets from '@/components/FormTargets.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import {
    completeAdviceMapping,
    makeAdviceMapping,
    makeField,
    makeFormDefinition,
    makeMapPointMapping,
    wellTypedAdviceFields,
} from '../fixtures/formDefinition';

type FormDefinitionData = App.Data.FormDefinitionData;

/** Both cards render the same labels, so every lookup is scoped to one target. */
type Target = 'advice' | 'map-point';

function mountWith(formDefinition: FormDefinitionData) {
    return mount(FormTargets, { props: { formDefinition } });
}

function statusOf(wrapper: ReturnType<typeof mountWith>, target: Target) {
    const badge = wrapper.find(`[data-test="${target}-status"]`);

    return badge.exists() ? badge.text() : null;
}

function listItemsIn(wrapper: ReturnType<typeof mountWith>, selector: string) {
    const block = wrapper.find(selector);

    return block.exists() ? block.findAll('li').map((item) => item.text()) : [];
}

function warningsOf(wrapper: ReturnType<typeof mountWith>, target: Target) {
    return listItemsIn(wrapper, `[data-test="${target}-warnings"]`);
}

function missingOf(wrapper: ReturnType<typeof mountWith>, target: Target) {
    return listItemsIn(wrapper, `[data-test="${target}-missing"]`);
}

describe('FormTargets advice mapping status', () => {
    it('is disabled when there is no mapping at all', () => {
        const wrapper = mountWith(makeFormDefinition());

        expect(statusOf(wrapper, 'advice')).toBeNull();
    });

    it('is disabled when the mapping exists but is switched off', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, enabled: false },
            }),
        );

        expect(statusOf(wrapper, 'advice')).toBe('Deaktiviert');
    });

    it('is complete when every required field and a direct advice type are set', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: completeAdviceMapping,
            }),
        );

        expect(statusOf(wrapper, 'advice')).toBe('Vollständig');
    });

    it.each([['first_name_field_id'], ['last_name_field_id'], ['address_field_id'], ['email_field_id'], ['phone_field_id']] as const)(
        'is incomplete when %s is missing',
        (field) => {
            const wrapper = mountWith(
                makeFormDefinition({
                    fields: wellTypedAdviceFields,
                    advice_mapping: { ...completeAdviceMapping, [field]: null },
                }),
            );

            expect(statusOf(wrapper, 'advice')).toBe('Unvollständig');
        },
    );

    it('is incomplete when neither a direct advice type nor a type field is set', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, advice_type_direct: null },
            }),
        );

        expect(statusOf(wrapper, 'advice')).toBe('Unvollständig');
    });

    describe('advice type resolved through a form field', () => {
        const typeField = makeField({
            id: 'f-type',
            type: 'radio',
            options: [
                { id: 'o-home', label: 'Vor Ort', value: 'home', sort_order: 0, is_default: false, is_required: false },
                { id: 'o-virtual', label: 'Digital', value: 'virtual', sort_order: 1, is_default: false, is_required: false },
            ],
        });

        const viaField = makeAdviceMapping({
            ...completeAdviceMapping,
            advice_type_direct: null,
            advice_type_field_id: 'f-type',
        });

        function mountViaField(mapping: Partial<App.Data.FormToAdviceMappingData>) {
            return mountWith(
                makeFormDefinition({
                    fields: [...wellTypedAdviceFields, typeField],
                    advice_mapping: { ...viaField, ...mapping },
                }),
            );
        }

        it('is complete once both option values are mapped', () => {
            const wrapper = mountViaField({
                advice_type_home_option_value: 'home',
                advice_type_virtual_option_value: 'virtual',
            });

            expect(statusOf(wrapper, 'advice')).toBe('Vollständig');
        });

        it('is incomplete when only the home option value is mapped', () => {
            const wrapper = mountViaField({ advice_type_home_option_value: 'home' });

            expect(statusOf(wrapper, 'advice')).toBe('Unvollständig');
        });

        it('is incomplete when only the virtual option value is mapped', () => {
            const wrapper = mountViaField({ advice_type_virtual_option_value: 'virtual' });

            expect(statusOf(wrapper, 'advice')).toBe('Unvollständig');
        });

        it('is incomplete when neither option value is mapped', () => {
            const wrapper = mountViaField({});

            expect(statusOf(wrapper, 'advice')).toBe('Unvollständig');
        });

        /**
         * A direct type wins over the field, so the option values stop being required.
         * The setter clears them when a direct type is picked, but data written before
         * that rule existed can still arrive in this shape.
         */
        it('ignores the missing option values when a direct type is also set', () => {
            const wrapper = mountViaField({ advice_type_direct: '0' });

            expect(statusOf(wrapper, 'advice')).toBe('Vollständig');
        });
    });
});

describe('FormTargets advice mapping warnings', () => {
    it('reports nothing when every mapped field has its expected type', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: completeAdviceMapping,
            }),
        );

        expect(warningsOf(wrapper, 'advice')).toEqual([]);
    });

    it.each([
        ['email_field_id', 'text', 'E-Mail Feld sollte vom Typ "email" sein'],
        ['address_field_id', 'text', 'Adresse Feld sollte vom Typ "address" sein'],
        ['phone_field_id', 'text', 'Telefon Feld sollte vom Typ "phone" sein'],
    ] as const)('warns when %s points at a %s field', (mappingKey, wrongType, expectedWarning) => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: [...wellTypedAdviceFields, makeField({ id: 'f-wrong', type: wrongType })],
                advice_mapping: { ...completeAdviceMapping, [mappingKey]: 'f-wrong' },
            }),
        );

        expect(warningsOf(wrapper, 'advice')).toContain(expectedWarning);
    });

    it('collects a warning per mismatched field', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: [...wellTypedAdviceFields, makeField({ id: 'f-wrong', type: 'text' })],
                advice_mapping: {
                    ...completeAdviceMapping,
                    email_field_id: 'f-wrong',
                    phone_field_id: 'f-wrong',
                },
            }),
        );

        expect(warningsOf(wrapper, 'advice')).toHaveLength(2);
    });

    /**
     * A mapping can outlive the field it points at. The status still counts the id as
     * present, so only the type warning is in question here - and it must not fire on
     * a field that cannot be found.
     */
    it('stays silent when a mapped field no longer exists', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, email_field_id: 'f-deleted' },
            }),
        );

        expect(warningsOf(wrapper, 'advice')).toEqual([]);
        expect(statusOf(wrapper, 'advice')).toBe('Vollständig');
    });
});

describe('FormTargets map point mapping', () => {
    const coordinateField = makeField({ id: 'f-coord', type: 'geo_coordinate' });
    const mapPointFields = [makeField({ id: 'f-title', type: 'text' }), makeField({ id: 'f-desc', type: 'textarea' }), coordinateField];

    const completeMapPointMapping = makeMapPointMapping({
        title_field_id: 'f-title',
        description_field_id: 'f-desc',
        coordinate_field_id: 'f-coord',
    });

    it('is disabled when switched off', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: mapPointFields,
                map_point_mapping: { ...completeMapPointMapping, enabled: false },
            }),
        );

        expect(statusOf(wrapper, 'map-point')).toBe('Deaktiviert');
    });

    it('is complete when all three fields are mapped', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: mapPointFields,
                map_point_mapping: completeMapPointMapping,
            }),
        );

        expect(statusOf(wrapper, 'map-point')).toBe('Vollständig');
    });

    it.each([['title_field_id'], ['description_field_id'], ['coordinate_field_id']] as const)('is incomplete when %s is missing', (field) => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: mapPointFields,
                map_point_mapping: { ...completeMapPointMapping, [field]: null },
            }),
        );

        expect(statusOf(wrapper, 'map-point')).toBe('Unvollständig');
    });

    it('warns when the coordinate mapping points at a non-coordinate field', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: mapPointFields,
                map_point_mapping: { ...completeMapPointMapping, coordinate_field_id: 'f-title' },
            }),
        );

        expect(warningsOf(wrapper, 'map-point')).toContain('Koordinaten Feld sollte vom Typ "geo_coordinate" sein');
    });
});

describe('FormTargets banner', () => {
    const incompleteBanner = 'Einige Ziele sind unvollständig konfiguriert';

    it('appears as soon as one target is incomplete', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, email_field_id: null },
            }),
        );

        expect(wrapper.text()).toContain(incompleteBanner);
    });

    it('stays hidden when every enabled target is complete', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: completeAdviceMapping,
            }),
        );

        expect(wrapper.text()).not.toContain(incompleteBanner);
    });

    it('stays hidden when both targets are switched off', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, enabled: false },
                map_point_mapping: makeMapPointMapping({ enabled: false }),
            }),
        );

        expect(wrapper.text()).not.toContain(incompleteBanner);
    });
});

describe('FormTargets missing mapping list', () => {
    it('stays hidden while the target is complete', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: completeAdviceMapping,
            }),
        );

        expect(wrapper.find('[data-test="advice-missing"]').exists()).toBe(false);
    });

    it.each([
        ['first_name_field_id', 'Vorname'],
        ['last_name_field_id', 'Nachname'],
        ['address_field_id', 'Adresse'],
        ['email_field_id', 'E-Mail'],
        ['phone_field_id', 'Telefon'],
    ] as const)('names %s as "%s"', (field, label) => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, [field]: null },
            }),
        );

        expect(missingOf(wrapper, 'advice')).toEqual([label]);
    });

    it('names the advice type when neither a field nor a direct type is set', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, advice_type_direct: null },
            }),
        );

        expect(missingOf(wrapper, 'advice')).toEqual(['Beratungstyp']);
    });

    it('names both option values when the advice type comes from a field', () => {
        const typeField = makeField({
            id: 'f-type',
            type: 'radio',
            options: [{ id: 'o-home', label: 'Vor Ort', value: 'home', sort_order: 0, is_default: false, is_required: false }],
        });

        const wrapper = mountWith(
            makeFormDefinition({
                fields: [...wellTypedAdviceFields, typeField],
                advice_mapping: {
                    ...completeAdviceMapping,
                    advice_type_direct: null,
                    advice_type_field_id: 'f-type',
                },
            }),
        );

        expect(missingOf(wrapper, 'advice')).toEqual(['Option für „Vor Ort“', 'Option für „Virtuell“']);
    });

    it('lists every missing field at once', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: makeAdviceMapping({ advice_type_direct: '0' }),
            }),
        );

        expect(missingOf(wrapper, 'advice')).toEqual(['Vorname', 'Nachname', 'Adresse', 'E-Mail', 'Telefon']);
    });

    it.each([
        ['title_field_id', 'Titel'],
        ['description_field_id', 'Beschreibung'],
        ['coordinate_field_id', 'Koordinaten'],
    ] as const)('names the map point field %s as "%s"', (field, label) => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: [makeField({ id: 'f-title', type: 'text' }), makeField({ id: 'f-desc', type: 'textarea' })],
                map_point_mapping: makeMapPointMapping({
                    title_field_id: 'f-title',
                    description_field_id: 'f-desc',
                    coordinate_field_id: 'f-coord',
                    [field]: null,
                }),
            }),
        );

        expect(missingOf(wrapper, 'map-point')).toEqual([label]);
    });

    /** The two cards each get their own list, rather than one shared summary. */
    it('keeps the advice and map point lists apart', () => {
        const wrapper = mountWith(
            makeFormDefinition({
                fields: wellTypedAdviceFields,
                advice_mapping: { ...completeAdviceMapping, email_field_id: null },
                map_point_mapping: makeMapPointMapping({ title_field_id: 'f-title', description_field_id: 'f-desc' }),
            }),
        );

        expect(missingOf(wrapper, 'advice')).toEqual(['E-Mail']);
        expect(missingOf(wrapper, 'map-point')).toEqual(['Koordinaten']);
    });
});
