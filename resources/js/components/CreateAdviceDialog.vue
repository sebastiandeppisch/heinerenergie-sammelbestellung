<script setup lang="ts">
import { isActingAsSystemAdmin, user } from '@/authHelper';
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import DialogScrollContent from '@/shadcn/components/ui/dialog/DialogScrollContent.vue';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/shadcn/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Textarea } from '@/shadcn/components/ui/textarea';
import type { CustomPageProps } from '@/types/pageProps';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Home, Phone, Plus, ShoppingCart } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);

const props = defineProps<{
    groups: App.Data.GroupData[];
}>();

const open = ref(false);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    street: '',
    street_number: '',
    zip: '',
    city: '',
    advice_status_id: 1,
    type: 0,
    commentary: '',
    advisor_id: user.value.id,
    group_id: null as string | null,
});

const adviceStatusItems = ref<{ id: number; name: string }[]>([]);
const adviceTypeItems = ref<{ id: number; name: string }[]>([]);

onMounted(() => {
    axios.get('api/advicestatus').then((r) => {
        adviceStatusItems.value = r.data;
    });
    axios.get('api/advicetypes').then((r) => {
        adviceTypeItems.value = r.data;
    });
});

const adviceStatusValue = computed({
    get: () => String(form.advice_status_id),
    set: (v: string) => {
        form.advice_status_id = Number(v);
    },
});

const adviceTypeValue = computed({
    get: () => String(form.type),
    set: (v: string) => {
        form.type = Number(v);
    },
});

const typeIcons: Record<string, any> = {
    Home,
    Virtual: Phone,
    DirectOrder: ShoppingCart,
};

function save() {
    form.post(route('advices.store'), {
        onError: (errors: Record<string, string>) => {
            Object.values(errors).forEach((msg) => toast.error(String(msg)));
        },
    });
}
</script>

<template>
    <Button data-test="new-advice-button" variant="outline" size="sm" @click="open = true">
        <Plus class="h-4 w-4" />
    </Button>

    <Dialog :open="open" @update:open="open = $event">
        <DialogScrollContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Neue Beratung</DialogTitle>
            </DialogHeader>

            <div class="grid grid-cols-2 gap-4 py-2">
                <div class="space-y-2">
                    <Label for="first_name">Vorname</Label>
                    <Input id="first_name" v-model="form.first_name" placeholder="Vorname" />
                </div>
                <div class="space-y-2">
                    <Label for="last_name">Nachname</Label>
                    <Input id="last_name" v-model="form.last_name" placeholder="Nachname" />
                </div>

                <div class="space-y-2">
                    <Label for="phone">Telefonnummer</Label>
                    <Input id="phone" v-model="form.phone" placeholder="Telefonnummer" />
                </div>
                <div class="space-y-2">
                    <Label for="email">E-Mail Adresse</Label>
                    <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" />
                </div>

                <div class="space-y-2">
                    <Label for="street">Straße</Label>
                    <Input id="street" v-model="form.street" placeholder="Straße" />
                </div>
                <div class="space-y-2">
                    <Label for="street_number">Hausnummer</Label>
                    <Input id="street_number" v-model="form.street_number" placeholder="Hausnummer" />
                </div>

                <div class="space-y-2">
                    <Label for="zip">Postleitzahl</Label>
                    <Input id="zip" v-model="form.zip" placeholder="12345" />
                </div>
                <div class="space-y-2">
                    <Label for="city">Stadt</Label>
                    <Input id="city" v-model="form.city" placeholder="Stadt" />
                </div>

                <div class="col-span-2 space-y-2">
                    <Label>Beratungstyp</Label>
                    <RadioGroup v-model="adviceTypeValue" class="flex gap-4">
                        <div v-for="typeItem in adviceTypeItems" :key="typeItem.id" class="flex items-center gap-2">
                            <RadioGroupItem :id="`type-${typeItem.id}`" :value="String(typeItem.id)" />
                            <Label :for="`type-${typeItem.id}`" class="flex cursor-pointer items-center gap-1">
                                <component :is="typeIcons[typeItem.name]" class="h-4 w-4" />
                                {{ typeItem.name }}
                            </Label>
                        </div>
                    </RadioGroup>
                </div>

                <div class="space-y-2">
                    <Label>Status</Label>
                    <Select v-model="adviceStatusValue">
                        <SelectTrigger>
                            <SelectValue placeholder="Status wählen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="status in adviceStatusItems" :key="status.id" :value="String(status.id)">
                                {{ status.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label>Gruppe</Label>
                    <Select v-if="isActingAsSystemAdmin" v-model="form.group_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Gruppe auswählen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="group in props.groups" :key="group.id" :value="group.id">
                                {{ group.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-else class="flex items-center rounded-md border border-input bg-muted/50 px-3 py-2 text-sm">
                        {{ currentGroup?.name ?? '—' }}
                    </div>
                </div>

                <div class="col-span-2 space-y-2">
                    <Label for="commentary">Kommentar</Label>
                    <Textarea id="commentary" v-model="form.commentary" placeholder="Kommentar..." />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" :disabled="form.processing" @click="open = false">Abbrechen</Button>
                <Button :disabled="form.processing" @click="save">Speichern</Button>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>
</template>
