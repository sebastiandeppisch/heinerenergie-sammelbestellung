import { consentLabels, consentText } from '@/utils/mapPointConsent';
import { describe, expect, it } from 'vitest';

const config: App.Data.MapPointConsentConfigData = {
    point_text: 'Karte von Gartenkreis mit Standort. {widerruf}',
    details_text: 'Karte von Gartenkreis mit: {felder} sowie dem Standort. {widerruf}',
    revocation_text: 'Widerruf über den Link per E-Mail.',
    revocation_url: null,
    base_labels: ['Titel', 'Beschreibung'],
    location_field_id: 'where',
    location_label: 'Ortsbeschreibung',
    subcategory_field_id: 'kind',
    default_category_labels: ['Foto'],
    category_labels: { pond: ['Foto', 'Wassertiefe'] },
    characteristics_field_id: 'measures',
    characteristics: [
        { option_value: 'gate', labels: ['Igeltor: Breite'], selectable_for: ['', 'pond'] },
        { option_value: 'deadwood', labels: ['Totholz: Menge'], selectable_for: [''] },
    ],
};

function plain(segments: Array<{ text: string }>): string {
    return segments.map((segment) => segment.text).join('');
}

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

    it('lists the location description only once it is filled in', () => {
        expect(consentLabels(config, { where: '  ' })).toEqual(['Titel', 'Beschreibung', 'Foto']);
        expect(consentLabels(config, { where: 'Am Brunnen' })).toEqual(['Titel', 'Beschreibung', 'Ortsbeschreibung', 'Foto']);
    });
});

describe('consentText', () => {
    it('shows the text of the first level without any fields', () => {
        expect(plain(consentText(config, { kind: 'pond' }, false))).toBe('Karte von Gartenkreis mit Standort. Widerruf über den Link per E-Mail.');
    });

    it('fills in the labels on the second level', () => {
        expect(plain(consentText(config, { kind: 'pond' }, true))).toBe(
            'Karte von Gartenkreis mit: Titel, Beschreibung, Foto, Wassertiefe sowie dem Standort. Widerruf über den Link per E-Mail.',
        );
    });

    it('links the privacy policy when there is no manage link', () => {
        const withPolicy = {
            ...config,
            revocation_text: 'Ich kann meine Einwilligung jederzeit widerrufen. Wie, steht in der Datenschutzerklärung.',
            revocation_url: 'https://example.org/datapolicy',
        };

        expect(consentText(withPolicy, {}, false)).toEqual([
            { text: 'Karte von Gartenkreis mit Standort. ' },
            { text: 'Ich kann meine Einwilligung jederzeit widerrufen. Wie, steht in der ' },
            { text: 'Datenschutzerklärung', url: 'https://example.org/datapolicy' },
            { text: '.' },
        ]);
    });
});
