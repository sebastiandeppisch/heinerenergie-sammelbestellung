import {
    filterFn_includesString,
    filterFn_inNumberRange,
    filterFn_weakEquals,
    sortFn_alphanumeric,
    sortFn_datetime,
    sortFn_text,
} from '@tanstack/vue-table';

/**
 * TanStack Table v9 only resolves `sortFn: 'auto'` against registered sort functions.
 * Without them, strings silently fall back to a case-sensitive basic sort.
 */
export const sortFns = {
    alphanumeric: sortFn_alphanumeric,
    text: sortFn_text,
    datetime: sortFn_datetime,
};

/**
 * TanStack Table v9 only resolves `filterFn: 'auto'` against registered filter functions.
 * Without them, column filters silently match every row.
 */
export const filterFns = {
    includesString: filterFn_includesString,
    inNumberRange: filterFn_inNumberRange,
    weakEquals: filterFn_weakEquals,
};
