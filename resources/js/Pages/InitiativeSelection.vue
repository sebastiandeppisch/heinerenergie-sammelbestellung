<script setup lang="ts">
import { Badge } from '@/shadcn/components/ui/badge';
import { router } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { route } from 'ziggy-js';
import MainPublic from '../layouts/MainPublic.vue';
import SingleCard from '../layouts/SingleCard.vue';

const props = defineProps<{
    initiatives: App.Data.GroupData[];
    canActAsSystemAdmin: boolean;
}>();

function selectInitiative(id: string, asAdmin: boolean = false) {
    router.post(route('actAsGroup', id), {
        asAdmin,
    });
}

function selectSystemAdmin() {
    router.post(route('actAsSystemAdmin'));
}

defineOptions({
    layout: MainPublic,
});
</script>

<template>
    <SingleCard
        title="Initiative auswählen"
        description="Wähle die Initiative, die Du gerade verwenden möchtest. Du kannst jederzeit zwischen den Initiativen oben rechts im User-Panel wechseln."
        :showBackLink="false"
        :fixedWidth="false"
    >
        <div class="mx-auto grid max-w-6xl grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6 py-6">
            <!-- Regular Initiative Cards -->
            <div
                v-for="initiative in initiatives"
                :key="initiative.id"
                class="flex h-[120px] cursor-pointer items-center justify-center rounded-lg border bg-card p-6 transition hover:-translate-y-0.5 hover:border-border hover:shadow-md"
                @click="selectInitiative(initiative.id)"
            >
                <div class="flex h-full w-full flex-col items-center justify-center">
                    <div v-if="initiative.logo_path" class="mb-4 flex w-full flex-1 items-center justify-center">
                        <img :src="initiative.logo_path" :alt="initiative.name" class="max-h-[60px] max-w-full object-contain" />
                    </div>
                    <div class="flex items-center gap-2 text-center text-lg font-medium text-foreground">
                        {{ initiative.name }}
                    </div>
                </div>
            </div>

            <!-- Admin Initiative Cards -->
            <div
                v-for="initiative in initiatives.filter((i) => i.userCanActAsAdmin)"
                :key="`${initiative.id}-admin`"
                class="flex h-[120px] cursor-pointer items-center justify-center rounded-lg border border-primary/20 bg-primary/2 p-6 transition hover:-translate-y-0.5 hover:bg-primary/5 hover:shadow-md"
                @click="selectInitiative(initiative.id, true)"
            >
                <div class="flex h-full w-full flex-col items-center justify-center">
                    <div v-if="initiative.logo_path" class="mb-4 flex w-full flex-1 items-center justify-center">
                        <img :src="initiative.logo_path" :alt="initiative.name" class="max-h-[60px] max-w-full object-contain" />
                    </div>
                    <div class="flex items-center gap-2 text-center text-lg font-medium text-foreground">
                        {{ initiative.name }}
                        <Badge variant="outline" class="border-primary/20 bg-primary/10 text-primary">Admin</Badge>
                    </div>
                </div>
            </div>

            <!-- System Admin Card -->
            <div
                v-if="canActAsSystemAdmin"
                class="flex h-[120px] cursor-pointer items-center justify-center rounded-lg border border-foreground/20 bg-foreground/2 p-6 transition hover:-translate-y-0.5 hover:bg-foreground/5 hover:shadow-md"
                @click="selectSystemAdmin"
            >
                <div class="flex h-full w-full flex-col items-center justify-center">
                    <div class="mb-4 flex w-full flex-1 items-center justify-center">
                        <KeyRound class="h-12 w-12 text-foreground/70" />
                    </div>
                    <div class="flex items-center gap-2 text-center text-lg font-medium text-foreground">System Admin</div>
                </div>
            </div>
        </div>
    </SingleCard>
</template>
