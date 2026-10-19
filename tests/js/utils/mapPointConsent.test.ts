import { consentLabels, consentText } from '@/utils/mapPointConsent';
import { describe, expect, it } from 'vitest';

const config: App.Data.MapPointConsentConfigData = {
    text: 'Karte von Gartenkreis mit: {felder} sowie dem Standort.',
    base_labels: ['Titel', 'Beschreibung'],
    subcategory_field_id: 'kind',
    default_category_labels: ['Foto'],
    category_labels: { pond: ['Foto', 'Wassertiefe'] },
    characteristics_field_id: 'measures',
    characteristics: [
        { option_value: 'gate', labels: ['Igeltor: Breite'], selectable_for: ['', 'pond'] },
        { option_value: 'deadwood', labels: ['Totholz: Menge'], selectable_for: [''] },
    ],
};

describe('consentLabels', () => {
    it('lists title, description and the public fields of the category without a choice', () => {
        expect(consentLabels(config, {})).toEqual(['Titel', 'Beschreibung', 'Foto']);
    });

    it('uses the fields of the chosen sub category', () => {
        expect(consentLabels(config, { kind: 'pond' })).toEqual(['Titel', 'Beschreibung', 'Foto', 'Wassertiefe']);
    });

    it('adds the fields of checked characteristics the category offers', () => {
        expect(consentLabels(config, { measures: ['deadwood', 'gate'] })).toEqual([
            'Titel',
            'Beschreibung',
            'Foto',
            'Igeltor: Breite',
            'Totholz: Menge',
        ]);
        expect(consentLabels(config, { kind: 'pond', measures: ['deadwood', 'gate'] })).toEqual([
            'Titel',
            'Beschreibung',
            'Foto',
            'Wassertiefe',
            'Igeltor: Breite',
        ]);
    });

    it('falls back to the category for an unknown option', () => {
        expect(consentLabels(config, { kind: 'unknown' })).toEqual(['Titel', 'Beschreibung', 'Foto']);
    });
});

describe('consentText', () => {
    it('fills in the labels', () => {
        expect(consentText(config, { kind: 'pond' })).toBe('Karte von Gartenkreis mit: Titel, Beschreibung, Foto, Wassertiefe sowie dem Standort.');
    });
});
