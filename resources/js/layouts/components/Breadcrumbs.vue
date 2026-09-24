<script setup lang="ts">
import type { BreadcrumbItemType } from '@/layouts/helper';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/shadcn/components/ui/breadcrumb';
import { Link } from '@inertiajs/vue3';

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                    <BreadcrumbLink v-if="item.href && index !== breadcrumbs.length - 1" as-child>
                        <Link :href="item.href">{{ item.title }}</Link>
                    </BreadcrumbLink>
                    <BreadcrumbPage v-else-if="index === breadcrumbs.length - 1">{{ item.title }}</BreadcrumbPage>
                    <span v-else>{{ item.title }}</span>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
