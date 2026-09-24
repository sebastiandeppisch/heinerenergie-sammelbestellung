type TreeCategory = {
    id: string;
    name: string;
    parent_id: string | null;
};

export type CategoryTreeEntry<T extends TreeCategory> = {
    category: T;
    depth: number;
};

/**
 * Orders the categories depth first, each parent directly followed by its sub categories, and keeps
 * the given order among siblings. A category whose parent is not in the list counts as a root, e.g.
 * when an embed shows only a sub category.
 */
export function flattenCategoryTree<T extends TreeCategory>(categories: Array<T>): Array<CategoryTreeEntry<T>> {
    const ids = new Set(categories.map((category) => category.id));
    const childrenByParentId = new Map<string | null, Array<T>>();

    for (const category of categories) {
        const parentId = category.parent_id !== null && ids.has(category.parent_id) ? category.parent_id : null;
        childrenByParentId.set(parentId, [...(childrenByParentId.get(parentId) ?? []), category]);
    }

    const entries: Array<CategoryTreeEntry<T>> = [];
    const visited = new Set<string>();

    function visit(parentId: string | null, depth: number) {
        for (const category of childrenByParentId.get(parentId) ?? []) {
            if (visited.has(category.id)) {
                continue;
            }
            visited.add(category.id);
            entries.push({ category, depth });
            visit(category.id, depth + 1);
        }
    }

    visit(null, 0);

    return entries;
}

/** The ids of the parent, grandparent and so on within the list, nearest first. */
export function ancestorIds(categories: Array<TreeCategory>, categoryId: string): Array<string> {
    const byId = new Map(categories.map((category) => [category.id, category]));
    const ancestors: Array<string> = [];
    let parentId = byId.get(categoryId)?.parent_id ?? null;

    while (parentId !== null && byId.has(parentId) && parentId !== categoryId && !ancestors.includes(parentId)) {
        ancestors.push(parentId);
        parentId = byId.get(parentId)?.parent_id ?? null;
    }

    return ancestors;
}

export function descendantIds(categories: Array<TreeCategory>, categoryId: string): Array<string> {
    return categories.filter((category) => ancestorIds(categories, category.id).includes(categoryId)).map((category) => category.id);
}

/** The names from the root down to the category, e.g. „Photovoltaik › Balkonkraftwerke“. */
export function categoryPath(categories: Array<TreeCategory>, categoryId: string): string {
    const byId = new Map(categories.map((category) => [category.id, category]));

    return [...ancestorIds(categories, categoryId).reverse(), categoryId]
        .map((id) => byId.get(id)?.name)
        .filter((name) => name !== undefined)
        .join(' › ');
}

/** A category is shown on the map when it and all of its ancestors are checked. */
export function isShownWithAncestors(categories: Array<TreeCategory>, visibility: Record<string, boolean>, categoryId: string): boolean {
    return [categoryId, ...ancestorIds(categories, categoryId)].every((id) => visibility[id]);
}

/** A checked category includes its sub categories, e.g. in an embed. */
export function isIncludedByAncestor(categories: Array<TreeCategory>, selection: Record<string, boolean>, categoryId: string): boolean {
    return ancestorIds(categories, categoryId).some((id) => selection[id]);
}
