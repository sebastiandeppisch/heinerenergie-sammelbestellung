<script setup lang="ts">
import FormSubmissionRenderer from '@/components/FormBuilder/FormSubmissionRenderer.vue';
import { ref } from 'vue';
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
}>();

const sharedIds = ref(props.advice.shares_ids || []);
const advisor = user.value;
</script>

<template>
    <div class="advice-container">
        <!-- Header Section -->
        <div class="advice-header">
            <div class="header-content">
                <div class="header-title-section">
                    <h2 class="advice-title">
                        Beratung für<br class="mobile-break" />
                        {{ advice.first_name }} {{ advice.last_name }}
                    </h2>
                </div>
                <div class="header-actions">
                    <AdviceActions :advice="advice" :advisor="advisor" :transferable-groups="transferableGroups" />
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="advice-content">
            <!-- Left Column - Main Information -->
            <div class="content-main">
                <div class="content-card">
                    <h3 class="card-title">Kontaktdaten & Details</h3>
                    <AdviceForm :advice="advice" />
                </div>

                <div class="content-card">
                    <h3 class="card-title">Beratungsteam</h3>
                    <AdviceSharing :advice-id="advice.id" v-model:shared-ids="sharedIds" />
                </div>
            </div>

            <!-- Right Column - Timeline and Details -->
            <div class="content-sidebar">
                <div class="content-card">
                    <h3 class="card-title">Verlauf</h3>
                    <AdviceTimeline :events="events" :advice-id="advice.id" />
                </div>

                <div class="content-card" v-if="props.formSubmission === null">
                    <h3 class="card-title">Zusätzliche Informationen</h3>
                    <AdviceDetails :advice="advice" />
                </div>
                <div class="content-card" v-else>
                    <h3 class="card-title">Zusätzliche Informationen aus dem Formular</h3>
                    <div style="padding: 1.5rem">
                        <FormSubmissionRenderer :form-submission="props.formSubmission" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.advice-container {
    min-height: 100vh;
    background-color: #f2f2f2;
}

.advice-header {
    background-color: #f2f2f2;
    border-bottom: 2px solid #e9ecef;
    padding: 16px 24px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title-section {
    flex: 1;
    min-width: 0; /* Verhindert Überlauf bei langen Namen */
}

.advice-title {
    font-size: 24px;
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
}

.mobile-break {
    display: none;
}

.header-actions {
    display: flex;
    gap: 16px;
    flex-shrink: 0;
}

.advice-content {
    max-width: 1400px;
    margin: 32px auto;
    padding: 0 24px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
}

.content-main,
.content-sidebar {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.content-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    padding: 20px 24px;
    margin: 0;
    border-bottom: 1px solid #e9ecef;
}

@media (max-width: 1200px) {
    .advice-content {
        grid-template-columns: 1fr;
    }

    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .advice-header {
        padding: 12px 16px;
    }

    .advice-title {
        font-size: 20px;
        white-space: normal;
    }

    .mobile-break {
        display: inline;
    }

    .advice-content {
        margin: 16px auto;
        padding: 0 16px;
        gap: 16px;
    }

    .content-main,
    .content-sidebar {
        gap: 16px;
    }
}
</style>
