<script setup lang="ts">
import AdviceMails from '@/components/AdviceMails.vue';
import AdviceGeocodingStatus from '@/components/Advices/AdviceGeocodingStatus.vue';
import ChecklistPanel from '@/components/ChecklistPanel.vue';
import FormSubmissionRenderer from '@/components/FormBuilder/FormSubmissionRenderer.vue';
import AdviceNextcloud from '@/components/Nextcloud/AdviceNextcloud.vue';
import PageHeader from '@/components/PageHeader.vue';
import Button from '@/shadcn/components/ui/button/Button.vue';
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
}>();

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
    <div>
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

        <!-- Main Content -->
        <div class="advice-content">
            <!-- Left Column - Main Information -->
            <div class="content-main">
                <div class="content-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center">
                        <h3 class="card-title">Kontaktdaten & Details</h3>
                        <Link :href="route('advices.map') + '#18/' + advice.lat + '/' + advice.lng" v-if="advice.lat && advice.lng" target="_blank">
                            <Button variant="outline" size="sm" title="Adresse auf der Karte anzeigen">
                                <Map class="mr-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                    <AdviceGeocodingStatus v-if="!advice.lat || !advice.lng" :advice="advice" style="padding: 0 16px 16px" />
                    <AdviceForm :advice="advice" :advice-status-options="adviceStatusOptions" :advice-types-options="adviceTypesOptions" />
                </div>

                <div class="content-card">
                    <h3 class="card-title card-header">Beratungsteam</h3>
                    <AdviceSharing :advice-id="advice.id" v-model:shared-ids="sharedIds" />
                </div>

                <div v-if="checklistEntries.length > 0 || availableChecklists.length > 0" class="content-card">
                    <h3 class="card-title card-header">Checklisten</h3>
                    <div style="padding: 16px">
                        <ChecklistPanel :checklist-entries="checklistEntries" :available-checklists="availableChecklists" :advice-id="advice.id" />
                    </div>
                </div>

                <div class="content-card" v-if="props.nextcloudConfigured">
                    <h3 class="card-title card-header">Dateien (Nextcloud)</h3>
                    <AdviceNextcloud :advice="advice" />
                </div>

                <div class="content-card" v-if="advice.email && user?.is_admin">
                    <h3 class="card-title card-header">E-Mails</h3>
                    <AdviceMails :advice-id="advice.id" :contact-email="advice.email" />
                </div>
            </div>

            <!-- Right Column - Timeline and Details -->
            <div class="content-sidebar">
                <div class="content-card" v-if="props.formSubmission === null">
                    <h3 class="card-title card-header">Zusätzliche Informationen</h3>
                    <AdviceDetails :advice="advice" />
                </div>
                <div class="content-card" v-else>
                    <h3 class="card-title card-header">Zusätzliche Informationen aus dem Formular</h3>
                    <div style="padding: 1.5rem">
                        <FormSubmissionRenderer :form-submission="props.formSubmission" />
                    </div>
                </div>

                <div class="content-card">
                    <h3 class="card-title card-header">Verlauf</h3>
                    <AdviceTimeline :events="events" :advice-id="advice.id" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.advice-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.content-main,
.content-sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.content-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.card-header {
    padding: 20px 24px;
    margin: 0;
    border-bottom: 1px solid #e9ecef;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

@media (max-width: 1200px) {
    .advice-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .advice-content,
    .content-main,
    .content-sidebar {
        gap: 16px;
    }
}
</style>
