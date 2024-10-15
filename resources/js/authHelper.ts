import { PageProps } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/vue3";
import { computed } from 'vue';

interface CustomPageProps extends PageProps {
  auth: {
    user: App.Models.User | null;
  }
}

const page = usePage<CustomPageProps>();
export const user = computed(() => page.props.auth.user);
export const email = computed(() => user.value?.email)
export const isAdmin = computed(() => user.value !== null && user.value.is_acting_as_admin)
