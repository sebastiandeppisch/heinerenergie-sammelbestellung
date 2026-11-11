import { defaultThresholds, levelFor, missingToNextLevel, scoreUnit, scoreUnitDative } from '@/components/HexNet/hexnet';
import { describe, expect, it } from 'vitest';

describe('scoring modes of a HexaNetz', () => {
    it('names the unit of each mode', () => {
        expect(scoreUnit(1, 'stones')).toBe('Trittstein');
        expect(scoreUnit(3, 'measures')).toBe('Maßnahmen');
        expect(scoreUnit(2, 'distinct_measures')).toBe('verschiedene Maßnahmen');
        expect(scoreUnitDative(3, 'stones')).toBe('Trittsteinen');
        expect(scoreUnitDative(4, 'distinct_measures')).toBe('verschiedenen Maßnahmen');
    });

    it('suggests all measures as dark for variety', () => {
        expect(defaultThresholds('distinct_measures', 11)).toEqual({ medium: 6, dark: 11 });
        expect(defaultThresholds('distinct_measures', 2)).toEqual({ medium: 2, dark: 3 });
        expect(defaultThresholds('stones', 11)).toEqual({ medium: 2, dark: 3 });
        expect(defaultThresholds('measures', 11)).toEqual({ medium: 3, dark: 6 });
    });

    it('is light from one unit and counts what is missing in the unit of the thresholds', () => {
        const thresholds = defaultThresholds('distinct_measures', 11);
        expect(levelFor(1, thresholds)).toBe(1);
        expect(levelFor(11, thresholds)).toBe(3);
        expect(missingToNextLevel(4, thresholds)).toEqual({ missing: 2, next: 'Mittelgrün' });
    });
});
