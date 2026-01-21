import type { LucideIcon } from 'lucide-vue-next';

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon;
    isActive?: boolean;
    role?: 'group-admin' | 'system-admin';
    children?: NavItem[];
}
