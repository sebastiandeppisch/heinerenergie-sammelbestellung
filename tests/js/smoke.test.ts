import { getInitials } from '@/composables/useInitials';
import { describe, expect, it } from 'vitest';

describe('test setup', () => {
    it('resolves the @ alias into resources/js', () => {
        expect(getInitials('Max Mustermann')).toBe('MM');
    });
});
