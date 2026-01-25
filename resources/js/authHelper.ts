import type { CustomPageProps } from '@/types/pageProps';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<CustomPageProps>();

export const isLoggedIn = computed(() => page.props.auth.user !== null);

export const user = computed(() => {
    const user = page.props.auth.user;
    if (user === null) {
        router.visit('/login-form');
    }
    return user as App.Data.UserData;
});
export const email = computed(() => user.value?.email);
export const isActingAsAdmin = computed(() => user.value !== null && user.value?.is_acting_as_admin);
export const isActingAsGroupAdmin = computed<boolean>(() => page.props.userRole === 'group-admin');
export const isActingAsSystemAdmin = computed<boolean>(() => page.props.userRole === 'system-admin');
