<script setup lang="ts">
import { Dialog, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import DialogScrollContent from '@/shadcn/components/ui/dialog/DialogScrollContent.vue';
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/shadcn/components/ui/sidebar';
import Tooltip from '@/shadcn/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '@/shadcn/components/ui/tooltip/TooltipContent.vue';
import TooltipTrigger from '@/shadcn/components/ui/tooltip/TooltipTrigger.vue';
import type { CustomPageProps } from '@/types/pageProps';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Loader2, Megaphone } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const page = usePage<CustomPageProps>();
const version = computed(() => page.props.version ?? 'dev');

/**
 * Erklärt das Versionsschema Jahr-Monat.Ausgabe, z. B. 2026-08.1.
 */
const versionExplanation = computed(() => {
    const match = /^(\d{4})-(\d{2})\.(\d+)$/.exec(version.value);

    if (!match) {
        return 'Entwicklungsstand ohne feste Versionsnummer.';
    }

    const [, year, month, release] = match;
    const monthName = new Date(Number(year), Number(month) - 1).toLocaleDateString('de-DE', { month: 'long' });

    return `Jahr-Monat.Ausgabe: die ${release}. Veröffentlichung im ${monthName} ${year}. So siehst Du auf einen Blick, wie aktuell die Anwendung ist.`;
});

const open = ref(false);
const isLoading = ref(false);
const hasLoaded = ref(false);
const changelog = ref<string | null>(null);

function setOpen(value: boolean) {
    open.value = value;
    if (value && !hasLoaded.value) {
        isLoading.value = true;
        axios
            .get(route('api.changelog'))
            .then((r) => {
                changelog.value = r.data.changelog;
                hasLoaded.value = true;
            })
            .finally(() => {
                isLoading.value = false;
            });
    }
}
</script>

<template>
    <SidebarGroup class="group-data-[collapsible=icon]:p-0">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem>
                    <Tooltip :delay-duration="300">
                        <TooltipTrigger as-child>
                            <SidebarMenuButton
                                class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                                @click="setOpen(true)"
                            >
                                <Megaphone />
                                <span>Version {{ version }}</span>
                            </SidebarMenuButton>
                        </TooltipTrigger>
                        <TooltipContent side="right" class="max-w-xs">
                            <p>{{ versionExplanation }}</p>
                            <p class="mt-1 text-muted-foreground">Klicken zeigt, was sich geändert hat.</p>
                        </TooltipContent>
                    </Tooltip>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>

    <Dialog :open="open" @update:open="setOpen">
        <DialogScrollContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Was ist neu? (Version {{ version }})</DialogTitle>
            </DialogHeader>

            <div v-if="isLoading" class="flex justify-center py-8">
                <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
            </div>
            <div
                v-else-if="changelog"
                class="text-sm [&_a]:text-primary [&_a]:underline [&_h1]:mb-2 [&_h1]:text-lg [&_h1]:font-semibold [&_h2]:mt-6 [&_h2]:border-t [&_h2]:pt-4 [&_h2]:text-base [&_h2]:font-semibold [&_h2:first-child]:mt-0 [&_h2:first-child]:border-t-0 [&_h2:first-child]:pt-0 [&_h3]:mt-4 [&_h3]:font-semibold [&_h4]:mt-3 [&_h4]:text-sm [&_h4]:font-medium [&_h4]:text-muted-foreground [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"
                v-html="changelog"
            ></div>
            <p v-else class="py-4 text-sm text-muted-foreground">Es liegen keine Änderungshinweise vor.</p>
        </DialogScrollContent>
    </Dialog>
</template>
