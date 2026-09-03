import { computed, onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/**
 * Sizes an element to `calc(100svh - X)` so it fills the space left below it in the
 * viewport without making the page itself scrollable.
 *
 * The viewport part stays in CSS on purpose: a JS-computed pixel height goes stale on
 * every resize, while `svh` follows the viewport on its own.
 */
export function useFillViewportHeight(el: Ref<HTMLElement | null>, minHeight = 320) {
    const offset = ref(0);
    const height = computed(() => `max(${minHeight}px, calc(100svh - ${offset.value}px))`);

    let resizeObserver: ResizeObserver | null = null;

    /**
     * X: everything the layout occupies around the element - the header and tabs above it,
     * plus whatever sits below it (the sidebar inset carries an 8px bottom margin).
     */
    function measure() {
        const node = el.value;
        if (!node) return;

        // Independent of our own height, so it can be read as-is
        const top = node.getBoundingClientRect().top + window.scrollY;

        // The space below has to be read while the element deliberately overflows the
        // viewport. The sidebar wrapper is `min-h-svh`, so on a page that does not
        // overflow it stretches the body down to the viewport and the gap below the
        // element would measure the leftover empty space instead of the layout's margin.
        const previousHeight = node.style.height;
        node.style.height = `${window.innerHeight}px`;
        const below = Math.max(document.body.getBoundingClientRect().bottom - node.getBoundingClientRect().bottom, 0);
        node.style.height = previousHeight;

        offset.value = Math.ceil(top + below);
    }

    /**
     * Everything rendered above the element, whose height therefore decides X: our own
     * previous siblings and those of every ancestor. The layout header is one of them and
     * shrinks when the sidebar collapses to icons, which fires no window resize.
     */
    function elementsAbove(node: HTMLElement): HTMLElement[] {
        const above: HTMLElement[] = [];
        for (let current: HTMLElement | null = node; current && current !== document.body; current = current.parentElement) {
            for (let sibling = current.previousElementSibling; sibling; sibling = sibling.previousElementSibling) {
                above.push(sibling as HTMLElement);
            }
        }
        return above;
    }

    onMounted(() => {
        measure();
        window.addEventListener('resize', measure);

        resizeObserver = new ResizeObserver(measure);
        if (el.value) {
            elementsAbove(el.value).forEach((node) => resizeObserver?.observe(node));
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', measure);
        resizeObserver?.disconnect();
        resizeObserver = null;
    });

    return { height, measure };
}
