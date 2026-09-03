<script setup lang="ts">
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import type { CustomPageProps } from '@/types/pageProps';
import { router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Key, ShieldAlert, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    message: string;
    intendedUrl?: string | null;
}>();

const emit = defineEmits<{
    back: [];
    switchRole: [];
}>();

const page = usePage<CustomPageProps>();

const user = computed(() => page.props.auth.user);
const currentGroup = computed(() => page.props.auth.currentGroup);

/**
 * Roles the user could switch into right now. We cannot tell which role the
 * denied action actually requires, so these are offered as a suggestion.
 */
const canSwitchToSystemAdmin = computed<boolean>(() => (user.value?.is_admin ?? false) && page.props.userRole !== 'system-admin');

const switchableAdminGroups = computed<App.Data.GroupData[]>(() =>
    (page.props.auth.availableGroups ?? []).filter(
        (group) => group.userCanActAsAdmin && !(page.props.userRole === 'group-admin' && currentGroup.value?.id === group.id),
    ),
);

const hasRoleOptions = computed<boolean>(() => canSwitchToSystemAdmin.value || switchableAdminGroups.value.length > 0);

function goToIntended(): void {
    if (props.intendedUrl && props.intendedUrl !== window.location.href) {
        router.visit(props.intendedUrl);
    }
}

function switchToSystemAdmin(): void {
    emit('switchRole');
    router.post(route('actAsSystemAdmin'), {}, { onSuccess: goToIntended });
}

function switchToGroupAdmin(groupId: string): void {
    emit('switchRole');
    router.post(route('actAsGroup', groupId), { asAdmin: true }, { onSuccess: goToIntended });
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex items-start gap-3">
            <ShieldAlert class="mt-0.5 h-6 w-6 flex-shrink-0 text-destructive" />
            <p class="text-sm leading-relaxed">{{ message }}</p>
        </div>

        <div v-if="hasRoleOptions" class="flex flex-col gap-2">
            <p class="text-xs text-muted-foreground">Vielleicht fehlt Dir nur die passende Rolle. Du kannst wechseln zu:</p>

            <Button v-if="canSwitchToSystemAdmin" variant="outline" class="justify-start" @click="switchToSystemAdmin">
                <Key class="h-4 w-4" />
                Systemadministrator
            </Button>

            <Button
                v-for="group in switchableAdminGroups"
                :key="group.id"
                variant="outline"
                class="justify-between"
                @click="switchToGroupAdmin(group.id)"
            >
                <span class="flex items-center gap-2 text-left">
                    <img v-if="group.logo_path" :src="group.logo_path" class="h-4 w-4 flex-shrink-0 object-contain" alt="" />
                    <Users v-else class="h-4 w-4 flex-shrink-0" />
                    {{ group.name }}
                </span>
                <Badge variant="default">Admin</Badge>
            </Button>
        </div>

        <div class="flex justify-end">
            <Button variant="secondary" @click="emit('back')">
                <ArrowLeft class="h-4 w-4" />
                Zurück
            </Button>
        </div>
    </div>
</template>
