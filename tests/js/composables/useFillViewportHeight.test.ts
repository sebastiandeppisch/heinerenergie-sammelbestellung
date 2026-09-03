import { useFillViewportHeight } from '@/composables/useFillViewportHeight';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref, type Ref } from 'vue';

/**
 * Records what gets observed so the tests can assert which elements the composable
 * decided are "above" the target - that choice is invisible from the rendered output.
 */
class FakeResizeObserver {
    static instances: FakeResizeObserver[] = [];

    observed: Element[] = [];
    disconnected = false;

    constructor(public callback: ResizeObserverCallback) {
        FakeResizeObserver.instances.push(this);
    }

    observe(target: Element) {
        this.observed.push(target);
    }

    unobserve() {}

    disconnect() {
        this.disconnected = true;
    }
}

/** jsdom lays nothing out, so every rect the composable reads has to be supplied. */
function stubRect(node: Element, rect: { top?: number; bottom?: number }) {
    node.getBoundingClientRect = () => ({ top: 0, bottom: 0, ...rect }) as DOMRect;
}

function mountWith(el: Ref<HTMLElement | null>, minHeight?: number) {
    let api!: ReturnType<typeof useFillViewportHeight>;

    const wrapper = mount(
        defineComponent({
            setup() {
                api = minHeight === undefined ? useFillViewportHeight(el) : useFillViewportHeight(el, minHeight);
                return () => h('div');
            },
        }),
    );

    return { wrapper, api };
}

describe('useFillViewportHeight', () => {
    beforeEach(() => {
        FakeResizeObserver.instances = [];
        window.ResizeObserver = FakeResizeObserver as unknown as typeof ResizeObserver;
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('height', () => {
        it('subtracts the space above and below the element from the viewport', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;

            stubRect(target, { top: 100, bottom: 900 });
            stubRect(document.body, { bottom: 908 });

            const { api } = mountWith(ref(target));

            // 100 above + 8 below
            expect(api.height.value).toBe('max(320px, calc(100svh - 108px))');
        });

        it('rounds the offset up so the element never overflows by a subpixel', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;

            stubRect(target, { top: 100.2, bottom: 900 });
            stubRect(document.body, { bottom: 908.3 });

            const { api } = mountWith(ref(target));

            expect(api.height.value).toBe('max(320px, calc(100svh - 109px))');
        });

        it('honours a custom minimum height', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;

            stubRect(target, { top: 40, bottom: 500 });
            stubRect(document.body, { bottom: 500 });

            const { api } = mountWith(ref(target), 500);

            expect(api.height.value).toBe('max(500px, calc(100svh - 40px))');
        });

        it('never reports negative space below the element', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;

            // The element sticks out past the body, so bottom - bottom goes negative
            stubRect(target, { top: 50, bottom: 1000 });
            stubRect(document.body, { bottom: 900 });

            const { api } = mountWith(ref(target));

            expect(api.height.value).toBe('max(320px, calc(100svh - 50px))');
        });

        it('leaves the offset at zero when the ref is empty', () => {
            const { api } = mountWith(ref(null));

            expect(api.height.value).toBe('max(320px, calc(100svh - 0px))');
        });
    });

    describe('measure', () => {
        /**
         * measure() deliberately stretches the element to read the gap below it. If the
         * restore in the middle of that ever gets lost the height freezes at a pixel
         * value, which looks correct until the next resize - no rendered output changes,
         * so only a direct assertion catches it.
         */
        it('restores the inline height it borrowed while measuring', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;
            target.style.height = '42px';

            stubRect(target, { top: 10, bottom: 100 });
            stubRect(document.body, { bottom: 110 });

            const { api } = mountWith(ref(target));
            api.measure();

            expect(target.style.height).toBe('42px');
        });

        it('leaves no inline height behind on an element that never had one', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;

            stubRect(target, { top: 10, bottom: 100 });
            stubRect(document.body, { bottom: 110 });

            const { api } = mountWith(ref(target));
            api.measure();

            expect(target.style.height).toBe('');
        });

        it('picks up a changed layout on re-measure', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;

            stubRect(target, { top: 100, bottom: 900 });
            stubRect(document.body, { bottom: 900 });

            const { api } = mountWith(ref(target));
            expect(api.height.value).toBe('max(320px, calc(100svh - 100px))');

            // The header collapsed, so the element moved up
            stubRect(target, { top: 60, bottom: 900 });
            api.measure();

            expect(api.height.value).toBe('max(320px, calc(100svh - 60px))');
        });
    });

    describe('observed elements', () => {
        it('observes every preceding sibling up the ancestor chain', () => {
            document.body.innerHTML = `
                <div id="header"></div>
                <div id="main">
                    <div id="tabs"></div>
                    <div id="target"></div>
                </div>
            `;
            const target = document.getElementById('target') as HTMLElement;

            mountWith(ref(target));

            const observed = FakeResizeObserver.instances[0].observed;

            // The element's own preceding sibling, plus its parent's
            expect(observed).toContain(document.getElementById('tabs'));
            expect(observed).toContain(document.getElementById('header'));
            expect(observed).toHaveLength(2);
        });

        it('ignores siblings that follow the element', () => {
            document.body.innerHTML = `
                <div id="tabs"></div>
                <div id="target"></div>
                <div id="footer"></div>
            `;
            const target = document.getElementById('target') as HTMLElement;

            mountWith(ref(target));

            const observed = FakeResizeObserver.instances[0].observed;

            expect(observed).toEqual([document.getElementById('tabs')]);
        });

        it('re-measures when an element above it resizes', () => {
            document.body.innerHTML = `
                <div id="header"></div>
                <div id="target"></div>
            `;
            const target = document.getElementById('target') as HTMLElement;

            stubRect(target, { top: 200, bottom: 900 });
            stubRect(document.body, { bottom: 900 });

            const { api } = mountWith(ref(target));
            expect(api.height.value).toBe('max(320px, calc(100svh - 200px))');

            // The sidebar collapsed to icons: the header shrank without a window resize
            stubRect(target, { top: 120, bottom: 900 });
            FakeResizeObserver.instances[0].callback([], {} as ResizeObserver);

            expect(api.height.value).toBe('max(320px, calc(100svh - 120px))');
        });
    });

    describe('cleanup', () => {
        it('drops the resize listener and the observer on unmount', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;
            stubRect(target, { top: 10, bottom: 100 });
            stubRect(document.body, { bottom: 100 });

            const removeListener = vi.spyOn(window, 'removeEventListener');

            const { wrapper } = mountWith(ref(target));
            wrapper.unmount();

            expect(removeListener).toHaveBeenCalledWith('resize', expect.any(Function));
            expect(FakeResizeObserver.instances[0].disconnected).toBe(true);
        });

        it('stops re-measuring after unmount', () => {
            document.body.innerHTML = '<div id="target"></div>';
            const target = document.getElementById('target') as HTMLElement;
            stubRect(target, { top: 100, bottom: 900 });
            stubRect(document.body, { bottom: 900 });

            const { wrapper, api } = mountWith(ref(target));
            wrapper.unmount();

            stubRect(target, { top: 500, bottom: 900 });
            window.dispatchEvent(new Event('resize'));

            expect(api.height.value).toBe('max(320px, calc(100svh - 100px))');
        });
    });
});
