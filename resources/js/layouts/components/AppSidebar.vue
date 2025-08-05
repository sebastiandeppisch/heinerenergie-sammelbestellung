<script setup lang="ts">
import NavFooter from '@/layouts/components/NavFooter.vue';
import NavMain from '@/layouts/components/NavMain.vue';
import NavUser from '@/layouts/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/shadcn/components/ui/sidebar';
import { type NavItem } from '@/layouts/helper';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Table, Map, Users, Settings, FileText, MessageSquare, User, Github } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { route } from 'ziggy-js';
import Card from '@/shadcn/components/ui/card/Card.vue';
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Beratungen - Tabelle',
        href: '/advices',
        icon: Table,
    },
    {
        title: 'Beratungen - Karte',
        href: '/advicesmap',
        icon: Map,
    },
    {
        title: 'Initiativen',
        href: '/groups',
        icon: Users,
    },
    {
        title: 'Berater*innen',
        href: '/users',
        icon: User,
    },
    {
        title: 'Einstellungen',
        href: '/settings',
        icon: Settings,
    },
    {
        title: 'Formulare',
        href: '/form-definitions',
        icon: FileText,
    },
    {
        title: 'Formulareinträge',
        href: '/form-submissions',
        icon: MessageSquare,
    },
    {
        title: 'Kartenpunkte - Tabelle',
        href: '/mappoints',
        icon: Table,
    },
    {
        title: 'Kartenpunkte - Karte',
        href: '/mappoints-map',
        icon: Map,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github',
        href: 'https://github.com/sebastiandeppisch/heinerenergie-sammelbestellung',
        icon: Github,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="bg-white hover:bg-white hover:shadow-md">
                        <Card>
                            <Link :href="route('dashboard')" class="w-full h-full flex justify-center">
                                <AppLogo />
                            </Link>
                        </Card>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
