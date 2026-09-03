import type { CustomPageProps } from '@/types/pageProps';
import { router } from '@inertiajs/vue3';

type Theme = CustomPageProps['theme'] | undefined;

function setCssVariable(property: string, value: number | null | undefined): void {
    const el = document.documentElement;

    if (value != null) {
        el.style.setProperty(property, String(value));
    } else {
        el.style.removeProperty(property);
    }
}

/**
 * Writes the initiative's primary color into the CSS custom properties the
 * shadcn theme is built on.
 */
export function applyTheme(theme: Theme): void {
    setCssVariable('--primary-hue', theme?.primaryHue);
    setCssVariable('--primary-lightness', theme?.primaryLightness);
    setCssVariable('--primary-chroma', theme?.primaryChroma);
}

/**
 * Keeps the primary color in sync with the shared `theme` prop for every page,
 * independent of the layout a page uses. Public pages (forms, embedded maps)
 * render without the app layout and would otherwise stay on the default color.
 */
export function initTheme(initialTheme: Theme): void {
    applyTheme(initialTheme);

    router.on('success', (event) => {
        applyTheme((event.detail.page.props as unknown as CustomPageProps).theme);
    });
}
