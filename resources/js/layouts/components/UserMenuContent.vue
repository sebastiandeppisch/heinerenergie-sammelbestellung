<script setup lang="ts">
import UserInfo from '@/layouts/components/UserInfo.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/shadcn/components/ui/dropdown-menu';
import type { CustomPageProps } from '@/types/pageProps';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Key, LogOut, User, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage<CustomPageProps>();
const user = computed<App.Data.UserData>(() => {
    if(page.props.auth.user) {
        return page.props.auth.user;
    }
    throw new Error('User not found');
});
const currentGroup = computed(() => page.props.auth.currentGroup);
const availableGroups = computed(() => page.props.auth.availableGroups || []);

function switchGroup(groupId: string, asAdmin: boolean) {
    router.post(route('actAsGroup', groupId), {
        asAdmin: asAdmin,
    });
}

function switchToSystemAdmin() {
    router.post('/actAsSystemAdmin');
}

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />

    <!-- Standard Menu Items -->
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="route('profile')" prefetch as="button">
                <User class="mr-2 h-4 w-4" />
                Profil
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <!-- Current Group Section -->
    <template v-if="currentGroup">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="text-xs text-muted-foreground"> Aktuelle Ansicht: </DropdownMenuLabel>
        <DropdownMenuGroup>
            <DropdownMenuItem class="flex min-h-[2.5rem] cursor-default items-start justify-between" disabled>
                <div class="flex items-start">
                    <img v-if="currentGroup.logo_path" :src="currentGroup.logo_path" class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0 object-contain" />
                    <Users v-else class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0" />
                    <span class="text-left leading-tight">{{ currentGroup.name }}</span>
                </div>
                <Badge v-if="user.is_acting_as_admin" variant="default" class="ml-2 flex-shrink-0"> Admin </Badge>
            </DropdownMenuItem>
        </DropdownMenuGroup>
    </template>

    <!-- Available Groups Section -->
    <template v-if="availableGroups.length > 0">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="text-xs text-muted-foreground"> Wechseln zu: </DropdownMenuLabel>
        <DropdownMenuGroup>
            <template v-for="group in availableGroups" :key="group.id">
                <!-- Regular view -->
                <DropdownMenuItem :as-child="true">
                    <button @click="switchGroup(group.id, false)" class="flex min-h-[2.5rem] w-full items-start justify-between text-left">
                        <div class="flex items-start">
                            <img v-if="group.logo_path" :src="group.logo_path" class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0 object-contain" />
                            <Users v-else class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0" />
                            <span class="leading-tight">{{ group.name }}</span>
                        </div>
                    </button>
                </DropdownMenuItem>

                <!-- Admin view (if user can act as admin) -->
                <DropdownMenuItem v-if="group.userCanActAsAdmin" :as-child="true">
                    <button @click="switchGroup(group.id, true)" class="flex min-h-[2.5rem] w-full items-start justify-between text-left">
                        <div class="flex items-start">
                            <img v-if="group.logo_path" :src="group.logo_path" class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0 object-contain" />
                            <Users v-else class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0" />
                            <span class="leading-tight">{{ group.name }}</span>
                        </div>
                        <Badge variant="default" class="ml-2 flex-shrink-0"> Admin </Badge>
                    </button>
                </DropdownMenuItem>
            </template>
        </DropdownMenuGroup>
    </template>

    <!-- System Admin Option -->
    <template v-if="user.is_admin">
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
            <DropdownMenuItem :as-child="true">
                <button @click="switchToSystemAdmin" class="flex w-full items-center">
                    <Key class="mr-2 h-4 w-4" />
                    System Admin
                </button>
            </DropdownMenuItem>
        </DropdownMenuGroup>
    </template>

    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full" method="post" :href="route('logout')" @click="handleLogout" as="button">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
