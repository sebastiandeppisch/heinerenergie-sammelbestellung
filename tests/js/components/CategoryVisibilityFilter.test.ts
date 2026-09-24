import CategoryVisibilityFilter from '@/components/CategoryVisibilityFilter.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

function makeCategory(id: string, parentId: string | null): App.Data.MapPointCategoryData {
    return {
        id,
        name: id,
        image_path: null,
        marker_image_path: null,
        parent_id: parentId,
        group_id: 'group',
        group_name: 'Initiative',
        map_points_count: 0,
        created_at: null,
        can_edit: true,
    };
}

const categories = [makeCategory('pv', null), makeCategory('balcony', 'pv')];

function checkbox(wrapper: ReturnType<typeof mount>, categoryId: string) {
    return wrapper.find(`[data-test="category-filter-${categoryId}"] [role="checkbox"]`);
}

describe('CategoryVisibilityFilter', () => {
    it('indents sub categories below their parent', () => {
        const wrapper = mount(CategoryVisibilityFilter, {
            props: { categories, idPrefix: 'filter-', visibility: { pv: true, balcony: true } },
        });

        expect(wrapper.find('[data-test="category-filter-balcony"]').attributes('style')).toContain('padding-left: 1.25rem');
    });

    it('shows sub categories as unchecked while their parent is hidden on a map', async () => {
        const wrapper = mount(CategoryVisibilityFilter, {
            props: {
                categories,
                idPrefix: 'filter-',
                visibility: { pv: true, balcony: true },
                'onUpdate:visibility': (visibility: Record<string, boolean>) => wrapper.setProps({ visibility }),
            },
        });

        await checkbox(wrapper, 'pv').trigger('click');

        expect(checkbox(wrapper, 'balcony').attributes('data-state')).toBe('unchecked');
        expect(checkbox(wrapper, 'balcony').attributes('disabled')).toBeDefined();

        await checkbox(wrapper, 'pv').trigger('click');

        expect(checkbox(wrapper, 'balcony').attributes('data-state')).toBe('checked');
        expect(checkbox(wrapper, 'balcony').attributes('disabled')).toBeUndefined();
    });

    it('marks sub categories as included while their parent is selected in an embed', () => {
        const wrapper = mount(CategoryVisibilityFilter, {
            props: { categories, idPrefix: 'filter-', includeDescendants: true, visibility: { pv: true, balcony: false } },
        });

        expect(checkbox(wrapper, 'balcony').attributes('data-state')).toBe('checked');
        expect(checkbox(wrapper, 'balcony').attributes('disabled')).toBeDefined();
    });

    it('lets sub categories be selected on their own while the parent is not selected in an embed', async () => {
        const wrapper = mount(CategoryVisibilityFilter, {
            props: {
                categories,
                idPrefix: 'filter-',
                includeDescendants: true,
                visibility: { pv: false, balcony: false },
                'onUpdate:visibility': (visibility: Record<string, boolean>) => wrapper.setProps({ visibility }),
            },
        });

        await checkbox(wrapper, 'balcony').trigger('click');

        expect(wrapper.props('visibility')).toEqual({ pv: false, balcony: true });
    });
});
