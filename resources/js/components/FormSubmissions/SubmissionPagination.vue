<template>
    <Pagination
        :items-per-page="pagination.perPage"
        :total="pagination.total"
        :sibling-count="1"
        show-edges
        :page="pagination.currentPage"
        @update:page="navigateToPage"
    >
        <PaginationContent v-slot="{ items }">
            <PaginationFirst />
            <PaginationPrevious />
            <template v-for="(item, index) in items">
                <PaginationItem v-if="item.type === 'page'" :key="index" :value="item.value" :is-active="item.value === pagination.currentPage">
                    {{ item.value }}
                </PaginationItem>
                <PaginationEllipsis v-else :key="`ellipsis-${index}`" :index="index" />
            </template>
            <PaginationNext />
            <PaginationLast />
        </PaginationContent>
    </Pagination>
</template>

<script lang="ts" setup>
import Pagination from '@/shadcn/components/ui/pagination/Pagination.vue';
import PaginationContent from '@/shadcn/components/ui/pagination/PaginationContent.vue';
import PaginationEllipsis from '@/shadcn/components/ui/pagination/PaginationEllipsis.vue';
import PaginationFirst from '@/shadcn/components/ui/pagination/PaginationFirst.vue';
import PaginationItem from '@/shadcn/components/ui/pagination/PaginationItem.vue';
import PaginationLast from '@/shadcn/components/ui/pagination/PaginationLast.vue';
import PaginationNext from '@/shadcn/components/ui/pagination/PaginationNext.vue';
import PaginationPrevious from '@/shadcn/components/ui/pagination/PaginationPrevious.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    pagination: App.Data.PaginationData<App.Data.FormSubmissionData>;
}>();

function navigateToPage(page: number) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));
    router.get(url.pathname + url.search, {}, { preserveScroll: true });
}
</script>
