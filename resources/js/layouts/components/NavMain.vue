<script setup lang="ts">
import { isActingAsAdmin, isActingAsSystemAdmin } from '@/authHelper';
import { type NavItem } from '@/layouts/helper';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/shadcn/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { CustomPageProps } from '@/types/pageProps';

defineProps<{
    items: NavItem[];
}>();

const page = usePage<CustomPageProps>();

const groupName = computed<string>(() => {
    const name = page.props.auth.currentGroup?.name || '';

    if (page.props.auth.currentGroup === null && page.props.auth.user?.is_acting_as_admin) {
        return 'Systemadministrator';
    }

    if (page.props.auth.user?.is_acting_as_admin ?? false) {
        return name + ' (Admin)';
    }

    return name;
});

function showItem(item: NavItem): boolean {
    if (!item.role) {
        return true;
    }

    if (item.role === 'group-admin' && isActingAsAdmin.value) {
        return true;
    }

    if (item.role === 'system-admin' && isActingAsSystemAdmin.value) {
        return true;
    }
    return false;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ groupName }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- without children -->
                <SidebarMenuItem v-if="!item.children && item.href && showItem(item)">
                    <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title" class="text-sidebar-accent-foreground">
                        <Link :href="item.href">
                            <component :is="item.icon" class="text-sidebar-accent-foreground" />
                            <span class="text-sidebar-accent-foreground">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- with children -->
                <SidebarMenuItem v-else-if="item.children && showItem(item)">
                    <SidebarMenuButton class="text-sidebar-accent-foreground">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </SidebarMenuButton>
                    <SidebarMenuSub>
                        <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                            <SidebarMenuSubButton
                                v-if="child.href && showItem(child)"
                                as-child
                                :is-active="child.href === page.url"
                                class="text-sidebar-accent-foreground"
                            >
                                <Link :href="child.href">
                                    <component :is="child.icon" class="text-sidebar-accent-foreground" />
                                    <span class="text-sidebar-accent-foreground">{{ child.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
