<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from "@/shadcn/components/ui/sidebar";
import { type NavItem } from "@/layouts/helper";
import { Link, usePage } from "@inertiajs/vue3";
import { PageProps } from "@inertiajs/core";

defineProps<{
    items: NavItem[];
}>();

interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData;
        currentGroup?: App.Data.GroupBaseData;
    };
}

const page = usePage<CustomPageProps>();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{
            page.props.auth.currentGroup?.name
        }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- without children -->
                <SidebarMenuItem v-if="!item.children && item.href">
                    <SidebarMenuButton
                        as-child
                        :is-active="item.href === page.url"
                        :tooltip="item.title"
                        class="text-sidebar-accent-foreground"
                    >
                        <Link :href="item.href">
                            <component
                                :is="item.icon"
                                class="text-sidebar-accent-foreground"
                            />
                            <span class="text-sidebar-accent-foreground">{{
                                item.title
                            }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- with children -->
                <SidebarMenuItem v-else-if="item.children">
                    <SidebarMenuButton class="text-sidebar-accent-foreground">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </SidebarMenuButton>
                    <SidebarMenuSub>
                        <SidebarMenuSubItem
                            v-for="child in item.children"
                            :key="child.title"
                        >
                            <SidebarMenuSubButton
                                v-if="child.href"
                                as-child
                                :is-active="child.href === page.url"
                                class="text-sidebar-accent-foreground"
                            >
                                <Link :href="child.href">
                                    <component
                                        :is="child.icon"
                                        class="text-sidebar-accent-foreground"
                                    />
                                    <span
                                        class="text-sidebar-accent-foreground"
                                        >{{ child.title }}</span
                                    >
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
