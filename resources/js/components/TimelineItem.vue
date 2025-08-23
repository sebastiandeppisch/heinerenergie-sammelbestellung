<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    event: App.Data.AdviceEventData;
}>();

function formatDate(date: string): string {
    return new Date(date).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const isSystem = !props.event.user_name;
const isMail = props.event.type === 'mail';
const isExpanded = ref(false);

function parseEmail(emailString: string[] | null): string {
    return emailString?.join(', ') ?? '';
}
</script>

<template>
    <div class="timeline-item" :class="{ 'timeline-item-system': isSystem && !isMail }">
        <div v-if="!isSystem" class="timeline-avatar">
            {{ props.event.initials }}
        </div>
        <div v-else-if="isMail" class="timeline-avatar timeline-avatar-mail">
            <i class="dx-icon-email"></i>
        </div>
        <div class="timeline-content" :class="{ 'timeline-content-system': isSystem, 'timeline-content-mail': isMail }">
            <div class="timeline-header">
                <span class="timeline-author">{{ props.event.user_name || (isMail ? 'E-Mail' : 'System') }}</span>
                <span class="timeline-date">{{ formatDate(props.event.created_at) }}</span>
            </div>
            <div class="timeline-message" @click="isMail && (isExpanded = !isExpanded)">
                <div v-if="isMail" class="mail-preview">
                    <div><strong>An:</strong> {{ props.event.to }}</div>
                    <div><strong>Betreff:</strong> {{ props.event.subject }}</div>
                </div>
                <div v-else>{{ props.event.description }}</div>
                <i v-if="isMail" :class="isExpanded ? 'dx-icon-chevronup' : 'dx-icon-chevrondown'" class="expand-icon"></i>
            </div>
            <!-- E-Mail Content -->
            <div v-if="isMail && isExpanded" class="timeline-mail-content">
                <iframe v-if="props.event.content !== null" :srcdoc="props.event.content" class="mail-iframe"></iframe>
            </div>
        </div>
    </div>
</template>

<style scoped>
.timeline-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
}

.timeline-item-system {
    padding-left: 48px;
}

.timeline-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary-color, #2196f3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    flex-shrink: 0;
    font-size: 0.85em;
}

.timeline-avatar-mail {
    background: #f9c300;
}

.timeline-content {
    flex: 1;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 12px;
    position: relative;
}

.timeline-content-system {
    background: #eeeeee;
}

.timeline-content-mail {
    background: #f8f9fa;
}

.timeline-content-mail .timeline-author,
.timeline-content-mail .timeline-date,
.timeline-content-mail .timeline-message,
.timeline-content-mail .expand-icon {
    color: inherit;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.timeline-author {
    font-weight: 500;
    color: #333;
}

.timeline-date {
    font-size: 0.85em;
    color: #666;
}

.timeline-message {
    color: #333;
    line-height: 1.4;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.timeline-comment {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    color: #666;
    font-size: 0.95em;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 12px;
    width: 0;
    height: 0;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
    border-right: 6px solid #f8f9fa;
}

.timeline-content-system::before {
    display: none;
}

.timeline-mail-content {
    margin-top: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    color: #333;
}

.mail-preview {
    flex: 1;
}

.timeline-content-mail::before {
    border-right-color: #f8f9fa;
}

.mail-iframe {
    width: 100%;
    min-height: 300px;
    border: none;
    background: white;
    border-radius: 4px;
}

.expand-icon {
    font-size: 12px;
    margin-left: 8px;
}
</style>
