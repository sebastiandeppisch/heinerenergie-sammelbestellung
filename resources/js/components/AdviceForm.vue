<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/shadcn/components/ui/radio-group';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Home, Phone, ShoppingCart, Save } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { toast } from 'vue-sonner';

const props = defineProps<{
    advice: App.Data.DataProtectedAdviceData;
    adviceStatusOptions: Array<{ id: string; name: string }>;
    adviceTypesOptions: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    first_name: props.advice.first_name,
    last_name: props.advice.last_name,
    phone: props.advice.phone ?? '',
    email: props.advice.email ?? '',
    street: props.advice.street ?? '',
    street_number: props.advice.street_number ?? '',
    zip: props.advice.zip ?? '',
    city: props.advice.city ?? '',
    advice_status_id: props.advice.advice_status_id,
    type: props.advice.type,
    commentary: props.advice.commentary ?? '',
});

function getAdviceTypeIcon(typeId: number) {
    // Assuming type IDs: 0 = Home, 1 = Virtual, 2 = DirectOrder
    const iconMap: Record<number, any> = {
        0: Home,
        1: Phone,
        2: ShoppingCart,
    };
    return iconMap[typeId] || Home;
}

function getAdviceTypeHelpText(typeId: number) {
    const helpTextMap: Record<number, string> = {
        0: 'Beratung vor Ort',
        1: 'Beratung per Telefon',
        2: 'Direktbestellung',
    };
    return helpTextMap[typeId] || '';
}

function onSubmit() {
    form.put(route('advices.update', props.advice.id), {
        preserveScroll: true,
        onSuccess: () => {
            //toast.success('Beratung gespeichert');
        },
        onError: (errors) => {
            // Show validation errors as toasts
            const errorMessages = Object.values(errors).flat();
            errorMessages.forEach((message) => {
                toast.error(message);
            });
        },
    });
}
</script>

<template>
    <div class="p-4">
        <form @submit.prevent="onSubmit" class="space-y-4">
            <!-- Name Group -->
            <div class="space-y-2">
                <Label class="text-sm font-semibold text-gray-700">Name</Label>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="first_name">Vorname</Label>
                        <Input id="first_name" v-model="form.first_name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="last_name">Nachname</Label>
                        <Input id="last_name" v-model="form.last_name" />
                    </div>
                </div>
            </div>

            <!-- Kontakt Group -->
            <div class="space-y-2">
                <Label class="text-sm font-semibold text-gray-700">Kontakt</Label>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="phone">Telefonnummer</Label>
                        <Input id="phone" v-model="form.phone" type="tel" />
                    </div>
                    <div class="space-y-2">
                        <Label for="email">E-Mail Adresse</Label>
                        <Input id="email" v-model="form.email" type="email" />
                    </div>
                </div>
            </div>

            <!-- Adresse Group -->
            <div class="space-y-2">
                <Label class="text-sm font-semibold text-gray-700">Adresse</Label>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="street">Straße</Label>
                        <Input id="street" v-model="form.street" />
                    </div>
                    <div class="space-y-2">
                        <Label for="street_number">Hausnummer</Label>
                        <Input id="street_number" v-model="form.street_number" />
                    </div>
                    <div class="space-y-2">
                        <Label for="zip">Postleitzahl</Label>
                        <Input id="zip" v-model="form.zip" />
                    </div>
                    <div class="space-y-2">
                        <Label for="city">Stadt</Label>
                        <Input id="city" v-model="form.city" />
                    </div>
                </div>
            </div>

            <!-- Beratung Group -->
            <div class="space-y-2">
                <Label class="text-sm font-semibold text-gray-700">Beratung</Label>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="advice_status_id">Status</Label>
                        <Select v-model="form.advice_status_id">
                            <SelectTrigger id="advice_status_id">
                                <SelectValue placeholder="Status auswählen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="status in props.adviceStatusOptions" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label>Typ</Label>
                        <RadioGroup v-model="form.type" class="flex flex-row gap-4">
                            <div v-for="type in props.adviceTypesOptions" :key="type.id" class="flex items-center space-x-2">
                                <RadioGroupItem :value="type.id" :id="`type_${type.id}`" />
                                <Label :for="`type_${type.id}`" class="flex items-center gap-2 cursor-pointer">
                                    <component :is="getAdviceTypeIcon(type.id)" class="h-5 w-5" :title="getAdviceTypeHelpText(type.id)" />
                                    <span>{{ type.name }}</span>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="commentary">Kommentar</Label>
                    <Textarea id="commentary" v-model="form.commentary" class="min-h-[100px]" />
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <Button type="submit" variant="default" :disabled="!form.isDirty || form.processing" class="w-full sm:w-auto">
                    <Save class="h-4 w-4" />
                    Speichern
                </Button>
            </div>
        </form>
    </div>
</template>
