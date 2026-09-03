import { vi } from 'vitest';

/**
 * jsdom ships neither of these, and both are touched at module scope by code under
 * test - utils/media-query.ts calls matchMedia on import, useFillViewportHeight
 * constructs a ResizeObserver on mount. Individual tests can still install their own
 * instrumented versions on top.
 */

if (!window.matchMedia) {
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        // The deprecated pair is required: resources/js/utils/media-query.ts uses addListener
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    }));
}

if (!window.ResizeObserver) {
    window.ResizeObserver = class {
        observe() {}
        unobserve() {}
        disconnect() {}
    } as unknown as typeof ResizeObserver;
}
