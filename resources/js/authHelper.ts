import { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData | null;
    };
}

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
export const isAdmin = computed(() => user !== null && user.value?.is_acting_as_admin);
