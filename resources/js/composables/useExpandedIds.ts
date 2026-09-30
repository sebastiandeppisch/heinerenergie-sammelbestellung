import { ref } from 'vue';

/**
 * Tracks which rows of a table show their detail row.
 */
export function useExpandedIds() {
    const expandedIds = ref(new Set<string>());

    function isExpanded(id: string): boolean {
        return expandedIds.value.has(id);
    }

    function toggleExpanded(id: string) {
        const ids = new Set(expandedIds.value);
        if (ids.has(id)) {
            ids.delete(id);
        } else {
            ids.add(id);
        }
        expandedIds.value = ids;
    }

    return { isExpanded, toggleExpanded };
}
