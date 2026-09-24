import type { Component } from 'vue';

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface NavItem {
    title: string;
    href?: string;
    icon?: Component;
    isActive?: boolean;
    role?: 'group-admin' | 'system-admin';
    children?: NavItem[];
}
