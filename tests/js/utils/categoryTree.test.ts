import { categoryPath, flattenCategoryTree, isIncludedByAncestor, isShownWithAncestors } from '@/utils/categoryTree';
import { describe, expect, it } from 'vitest';

const categories = [
    { id: 'balcony', name: 'Balkonkraftwerke', parent_id: 'pv' },
    { id: 'heat-pumps', name: 'Wärmepumpen', parent_id: null },
    { id: 'pv', name: 'Photovoltaik', parent_id: null },
    { id: 'with-storage', name: 'Mit Speicher', parent_id: 'balcony' },
];

describe('categoryTree', () => {
    it('lists each category directly below its parent with its depth', () => {
        const entries = flattenCategoryTree(categories).map(({ category, depth }) => [category.id, depth]);

        expect(entries).toEqual([
            ['heat-pumps', 0],
            ['pv', 0],
            ['balcony', 1],
            ['with-storage', 2],
        ]);
    });

    it('treats a category whose parent is missing as a root', () => {
        const entries = flattenCategoryTree(categories.filter((category) => category.id !== 'pv')).map(({ category, depth }) => [category.id, depth]);

        expect(entries).toEqual([
            ['balcony', 0],
            ['with-storage', 1],
            ['heat-pumps', 0],
        ]);
    });

    it('names the path from the root', () => {
        expect(categoryPath(categories, 'with-storage')).toBe('Photovoltaik › Balkonkraftwerke › Mit Speicher');
    });

    it('shows a category only while all of its ancestors are shown', () => {
        const visibility = { pv: false, balcony: true, 'with-storage': true, 'heat-pumps': true };

        expect(isShownWithAncestors(categories, visibility, 'with-storage')).toBe(false);
        expect(isShownWithAncestors(categories, visibility, 'heat-pumps')).toBe(true);
    });

    it('includes a category when any ancestor is selected', () => {
        const selection = { pv: true, balcony: false, 'with-storage': false, 'heat-pumps': false };

        expect(isIncludedByAncestor(categories, selection, 'with-storage')).toBe(true);
        expect(isIncludedByAncestor(categories, selection, 'pv')).toBe(false);
    });
});
