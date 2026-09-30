<script setup lang="ts">
import AdviceMails from '@/components/AdviceMails.vue';
import AdviceGeocodingStatus from '@/components/Advices/AdviceGeocodingStatus.vue';
import ChecklistPanel from '@/components/ChecklistPanel.vue';
import FormSubmissionRenderer from '@/components/FormBuilder/FormSubmissionRenderer.vue';
import AdviceNextcloud from '@/components/Nextcloud/AdviceNextcloud.vue';
import PageHeader from '@/components/PageHeader.vue';
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Link, setLayoutProps } from '@inertiajs/vue3';
import { Map } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { user } from '../authHelper';
import AdviceActions from '../components/AdviceActions.vue';
import AdviceDetails from '../components/AdviceDetails.vue';
import AdviceForm from '../components/AdviceForm.vue';
import AdviceSharing from '../components/AdviceSharing.vue';
import AdviceTimeline from '../components/AdviceTimeline.vue';

type AdviceEvent = App.Data.AdviceEventData;

const props = defineProps<{
    advice: App.Data.DataProtectedAdviceData;
    events: AdviceEvent[];
    transferableGroups: App.Data.GroupData[];
    formSubmission: App.Data.FormSubmissionData | null;
    adviceStatusOptions: Array<{ id: string; name: string }>;
    adviceTypesOptions: Array<{ id: number; name: string }>;
    canDeleteAdvice: boolean;
    checklistEntries: App.Data.ChecklistEntryData[];
    availableChecklists: App.Data.FormDefinitionData[];
    nextcloudConfigured: boolean;
    relatedAdvices: Array<{
        id: string;
        created_at: string;
        advisor_name: string | null;
        status_name: string | null;
        group_name: string | null;
        can_view: boolean;
    }>;
}>();

const formatDate = (iso: string) => (iso ? new Date(iso).toLocaleDateString('de-DE') : '');

const describeRelated = (related: (typeof props.relatedAdvices)[number]): string => {
    const parts = [related.advisor_name ?? 'nicht zugewiesen'];
    if (related.status_name) {
        parts.push(`Status: ${related.status_name}`);
    }
    if (related.group_name) {
        parts.push(`Gruppe: ${related.group_name}`);
    }
    return parts.join(', ');
};

setLayoutProps({
    breadcrumbs: [
        { title: 'Beratungen' },
        { title: 'Tabelle', href: route('advices') },
        { title: `${props.advice.first_name} ${props.advice.last_name}` },
    ],
});

const sharedIds = ref(props.advice.shares_ids || []);
const advisor = user.value;
</script>

<template>
    <div class="mx-auto max-w-7xl">
        <PageHeader :title="`Beratung für ${advice.first_name} ${advice.last_name}`">
            <template #actions>
                <AdviceActions
                    :advice="advice"
                    :advisor="advisor"
                    :transferable-groups="transferableGroups"
                    :can-delete-advice="props.canDeleteAdvice"
                />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[2fr_1fr] xl:gap-6">
            <!-- Left Column - Main Information -->
            <div class="flex flex-col gap-4 xl:gap-6">
                <Card>
                    <CardHeader class="border-b">
                        <CardTitle>Kontaktdaten & Details</CardTitle>
                        <CardAction v-if="advice.lat && advice.lng">
                            <Link :href="route('advices.map') + '#18/' + advice.lat + '/' + advice.lng" target="_blank">
                                <Button variant="outline" size="sm" title="Adresse auf der Karte anzeigen">
                                    <Map class="h-4 w-4" />
                                </Button>
                            </Link>
                        </CardAction>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <AdviceGeocodingStatus v-if="!advice.lat || !advice.lng" :advice="advice" />
                        <AdviceForm :advice="advice" :advice-status-options="adviceStatusOptions" :advice-types-options="adviceTypesOptions" />
                    </CardContent>
                </Card>

                <Card v-if="relatedAdvices.length > 0">
                    <CardHeader class="border-b">
                        <CardTitle>Weitere Beratungen mit dieser E-Mail-Adresse</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="flex flex-col gap-2">
                            <li v-for="related in relatedAdvices" :key="related.id">
                                <Link v-if="related.can_view" :href="route('advices.show', related.id)" class="underline">
                                    Beratung vom {{ formatDate(related.created_at) }}
                                </Link>
                                <span v-else>Beratung vom {{ formatDate(related.created_at) }}</span>
                                <span class="text-muted-foreground"> – {{ describeRelated(related) }}</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="border-b">
                        <CardTitle>Beratungsteam</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <AdviceSharing :advice-id="advice.id" v-model:shared-ids="sharedIds" />
                    </CardContent>
                </Card>

                <Card v-if="checklistEntries.length > 0 || availableChecklists.length > 0">
                    <CardHeader class="border-b">
                        <CardTitle>Checklisten</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ChecklistPanel :checklist-entries="checklistEntries" :available-checklists="availableChecklists" :advice-id="advice.id" />
                    </CardContent>
                </Card>

                <Card v-if="props.nextcloudConfigured">
                    <CardHeader class="border-b">
                        <CardTitle>Dateien (Nextcloud)</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <AdviceNextcloud :advice="advice" />
                    </CardContent>
                </Card>

                <Card v-if="advice.email && user?.is_admin">
                    <CardHeader class="border-b">
                        <CardTitle>E-Mails</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <AdviceMails :advice-id="advice.id" :contact-email="advice.email" />
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column - Timeline and Details -->
            <div class="flex flex-col gap-4 xl:gap-6">
                <Card>
                    <CardHeader class="border-b">
                        <CardTitle>{{
                            props.formSubmission === null ? 'Zusätzliche Informationen' : 'Zusätzliche Informationen aus dem Formular'
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <AdviceDetails v-if="props.formSubmission === null" :advice="advice" />
                        <FormSubmissionRenderer v-else :form-submission="props.formSubmission" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="border-b">
                        <CardTitle>Verlauf</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <AdviceTimeline :events="events" :advice-id="advice.id" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
