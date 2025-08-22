<script setup lang="ts">
import NavFooter from '@/layouts/components/NavFooter.vue';
import NavMain from '@/layouts/components/NavMain.vue';
import NavUser from '@/layouts/components/NavUser.vue';
import { type NavItem } from '@/layouts/helper';
import Card from '@/shadcn/components/ui/card/Card.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/shadcn/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';
import { FileText, Github, LayoutGrid, Map, MessageSquare, Settings, Table, User, UserCog, Users } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLogo from './AppLogo.vue';
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Beratungen',
        icon: User,
        children: [
            {
                title: 'Tabelle',
                href: '/advices',
                icon: Table,
            },
            {
                title: 'Karte',
                href: '/advicesmap',
                icon: Map,
            },
        ],
    },
    {
        title: 'Initiativen',
        icon: UserCog,
        children: [
            {
                title: 'Initiativen-Verwaltung',
                href: '/groups',
                icon: Users,
            },
            {
                title: 'Berater*innen',
                href: '/users',
                icon: User,
            },
        ],
    },

    {
        title: 'Formulare',
        icon: FileText,
        children: [
            {
                title: 'Formular-Verwaltung',
                href: '/form-definitions',
                icon: FileText,
            },
            {
                title: 'Formulareinträge',
                href: '/form-submissions',
                icon: MessageSquare,
            },
        ],
    },
    {
        title: 'Kartenpunkte',
        icon: Map,
        children: [
            {
                title: 'Tabelle',
                href: '/mappoints',
                icon: Table,
            },
            {
                title: 'Karte',
                href: '/mappoints-map',
                icon: Map,
            },
        ],
    },
    {
        title: 'Einstellungen',
        href: '/settings',
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github',
        href: 'https://github.com/sebastiandeppisch/heinerenergie-sammelbestellung',
        icon: Github,
    },
    {
        title: 'Impressum',
        href: '/impress',
        icon: FileText,
    },
    {
        title: 'Datenschutzerklärung',
        href: '/datapolicy',
        icon: FileText,
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
                            <Link :href="route('dashboard')" class="flex h-full w-full justify-center">
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
