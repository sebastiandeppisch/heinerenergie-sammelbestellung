<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { faWarning } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import { ArrowRight, Send } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    adviceId: string;
    transferableGroups: Array<App.Data.GroupData>;
}>();

const popupVisible = ref(false);
const reason = ref('');
const selectedGroup = ref<string | null>(null);

function showTransferPopup() {
    popupVisible.value = true;
}

function transferAdvice() {
    if (!selectedGroup.value) {
        toast.error('Bitte wähle eine Initiative aus', { duration: 2000 });
        return;
    }

    if (!reason.value) {
        toast.error('Bitte gib einen Grund für die Übertragung ein', { duration: 2000 });
        return;
    }

    router.post(
        `/advices/${props.adviceId}/transfer`,
        {
            group_id: selectedGroup.value,
            reason: reason.value,
        },
        {
            onSuccess: () => {
                popupVisible.value = false;
            },
        },
    );
}
</script>

<template>
    <div>
        <Button variant="outline" @click="showTransferPopup">
            <ArrowRight class="h-4 w-4" />
            Beratung übertragen
        </Button>

        <Dialog v-model:open="popupVisible">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Beratung übertragen</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label>Initiative auswählen</Label>
                        <Select v-model="selectedGroup">
                            <SelectTrigger>
                                <SelectValue placeholder="Initiative auswählen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="group in transferableGroups" :key="group.id" :value="String(group.id)">
                                    {{ group.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Grund für die Übertragung</label>
                        <Textarea v-model="reason" class="min-h-[100px]" placeholder="Grund für die Übertragung..." />
                    </div>
                    <div class="transfer-info">
                        <p class="info-text">
                            Die Beratung wird an die ausgewählte Initiative übertragen. Der/Die Klient:in wird per E-Mail benachrichtigt.
                        </p>
                        <p class="warning-text">
                            <FontAwesomeIcon :icon="faWarning" /> Du hast danach evtl. keine Berechtigung mehr, die Beratung zu sehen.
                        </p>
                    </div>
                    <Button variant="default" @click="transferAdvice" class="w-full">
                        <Send class="h-4 w-4" />
                        Übertragen
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
.transfer-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 12px;
}

.warning-text {
    color: red;
}

.info-text {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}
</style>
