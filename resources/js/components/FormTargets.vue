<script setup lang="ts">
import { Badge } from '@/shadcn/components/ui/badge';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { FormField, FormItem, FormLabel } from '@/shadcn/components/ui/form';
import { Select } from '@/shadcn/components/ui/select';
import SelectContent from '@/shadcn/components/ui/select/SelectContent.vue';
import SelectItem from '@/shadcn/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/shadcn/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/shadcn/components/ui/select/SelectValue.vue';
import { AlertTriangle, CheckCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
type FormDefinitionData = App.Data.FormDefinitionData;

const formDefinition = defineModel<FormDefinitionData>('formDefinition', { required: true });

// Validation helpers for targets
const adviceValidation = computed(() => {
    const mapping = formDefinition.value.advice_mapping;
    if (!mapping?.enabled) return { status: 'disabled', missing: [], warnings: [] };

    const required = [
        'first_name_field_id',
        'last_name_field_id',
        'address_field_id',
        'email_field_id',
        'phone_field_id',
        'advice_type_field_id',
        'advice_type_home_option_value',
        'advice_type_virtual_option_value',
    ] as const;
    const missing = required.filter((field) => !mapping[field]);

    const warnings: string[] = [];

    // Type validation
    if (mapping.email_field_id) {
        const field = formDefinition.value.fields.find((f: any) => f.id === mapping.email_field_id);
        if (field && field.type !== 'email') warnings.push('E-Mail Feld sollte vom Typ "email" sein');
    }

    if (mapping.address_field_id) {
        const field = formDefinition.value.fields.find((f: any) => f.id === mapping.address_field_id);
        if (field && field.type !== 'address') warnings.push('Adresse Feld sollte vom Typ "address" sein');
    }

    if (mapping.phone_field_id) {
        const field = formDefinition.value.fields.find((f: any) => f.id === mapping.phone_field_id);
        if (field && field.type !== 'phone') warnings.push('Telefon Feld sollte vom Typ "phone" sein');
    }

    return {
        status: missing.length === 0 ? 'complete' : 'incomplete',
        missing,
        warnings,
    };
});

const mapPointValidation = computed(() => {
    const mapping = formDefinition.value.map_point_mapping;
    if (!mapping?.enabled) return { status: 'disabled', missing: [], warnings: [] };

    const required = ['title_field_id', 'description_field_id', 'coordinate_field_id'] as const;
    const missing = required.filter((field) => !mapping[field]);

    const warnings: string[] = [];

    // Type validation
    if (mapping.coordinate_field_id) {
        const field = formDefinition.value.fields.find((f: any) => f.id === mapping.coordinate_field_id);
        if (field && field.type !== 'geo_coordinate') warnings.push('Koordinaten Feld sollte vom Typ "geo_coordinate" sein');
    }

    return {
        status: missing.length === 0 ? 'complete' : 'incomplete',
        missing,
        warnings,
    };
});

// Filter fields by type for dropdowns
const textFields = computed(() => formDefinition.value.fields.filter((f: any) => f.type === 'text'));
const emailFields = computed(() => formDefinition.value.fields.filter((f: any) => f.type === 'email'));
const phoneFields = computed(() => formDefinition.value.fields.filter((f: any) => f.type === 'phone'));
const addressFields = computed(() => formDefinition.value.fields.filter((f: any) => f.type === 'address'));
const geoCoordinateFields = computed(() => formDefinition.value.fields.filter((f: any) => f.type === 'geo_coordinate'));
const textareaFields = computed(() => formDefinition.value.fields.filter((f: any) => f.type === 'textarea'));
const selectFields = computed(() => formDefinition.value.fields.filter((f: any) => ['select', 'radio'].includes(f.type)));

// Get the options of the selected advice type field
const adviceTypeFieldOptions = computed(() => {
    const mapping = formDefinition.value.advice_mapping;
    if (!mapping?.advice_type_field_id) return [];

    const field = formDefinition.value.fields.find((f: any) => f.id === mapping.advice_type_field_id);
    return field?.options || [];
});
</script>

<template>
    <div class="space-y-6">
        <div
            v-if="adviceValidation.status === 'incomplete' || mapPointValidation.status === 'incomplete'"
            class="rounded-md border border-yellow-200 bg-yellow-50 p-4"
        >
            <div class="flex">
                <AlertTriangle class="h-4 w-4 text-yellow-600" />
                <div class="ml-3">
                    <p class="text-sm text-yellow-800">Einige Ziele sind unvollständig konfiguriert. Bitte vervollständige die Zuordnungen.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Advice Mapping Card -->
            <Card>
                <CardContent class="space-y-4" v-if="formDefinition.advice_mapping !== null">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold">Beratung erstellen</h3>
                            <Badge
                                :variant="
                                    adviceValidation.status === 'complete'
                                        ? 'default'
                                        : adviceValidation.status === 'incomplete'
                                          ? 'destructive'
                                          : 'secondary'
                                "
                                class="flex items-center gap-1"
                            >
                                <CheckCircle v-if="adviceValidation.status === 'complete'" class="h-3 w-3" />
                                <XCircle v-else-if="adviceValidation.status === 'incomplete'" class="h-3 w-3" />
                                <span v-if="adviceValidation.status === 'complete'">Vollständig</span>
                                <span v-else-if="adviceValidation.status === 'incomplete'">Unvollständig</span>
                                <span v-else>Deaktiviert</span>
                            </Badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="formDefinition.advice_mapping.enabled" />
                            <span class="text-sm">Aktiv</span>
                        </div>
                    </div>

                    <div v-if="formDefinition.advice_mapping.enabled" class="space-y-4">
                        <!-- Warnings -->
                        <div v-if="adviceValidation.warnings.length > 0" class="rounded-md border border-red-200 bg-red-50 p-4">
                            <div class="flex">
                                <AlertTriangle class="h-4 w-4 text-red-600" />
                                <div class="ml-3">
                                    <ul class="list-inside list-disc space-y-1 text-sm text-red-800">
                                        <li v-for="warning in adviceValidation.warnings" :key="warning">{{ warning }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <FormField v-slot="{ componentField }" name="first_name_field_id">
                                <FormItem>
                                    <FormLabel>Vorname *</FormLabel>
                                    <Select v-model="formDefinition.advice_mapping.first_name_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in textFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="last_name_field_id">
                                <FormItem>
                                    <FormLabel>Nachname *</FormLabel>
                                    <Select v-model="formDefinition.advice_mapping.last_name_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in textFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="address_field_id">
                                <FormItem>
                                    <FormLabel>Adresse *</FormLabel>
                                    <Select v-model="formDefinition.advice_mapping.address_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in addressFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="email_field_id">
                                <FormItem>
                                    <FormLabel>E-Mail *</FormLabel>
                                    <Select v-model="formDefinition.advice_mapping.email_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in emailFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="phone_field_id">
                                <FormItem>
                                    <FormLabel>Telefon *</FormLabel>
                                    <Select v-model="formDefinition.advice_mapping.phone_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in phoneFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="advice_type_field_id">
                                <FormItem>
                                    <FormLabel>Beratungstyp *</FormLabel>
                                    <Select v-model="formDefinition.advice_mapping.advice_type_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in selectFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <!-- Enum Mapping: Show when advice type field is selected -->
                            <div
                                v-if="formDefinition.advice_mapping.advice_type_field_id && adviceTypeFieldOptions.length > 0"
                                class="space-y-3 rounded-md border border-gray-200 bg-gray-50 p-4"
                            >
                                <h4 class="text-sm font-medium text-gray-900">Beratungstyp-Zuordnung</h4>
                                <p class="mb-3 text-xs text-gray-600">Ordne die Formular-Optionen den Beratungstypen zu:</p>

                                <FormField v-slot="{ componentField }" name="advice_type_home_option_value">
                                    <FormItem>
                                        <FormLabel class="text-xs">Option für Vor Ort" *</FormLabel>
                                        <Select v-model="formDefinition.advice_mapping.advice_type_home_option_value">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Option auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="option in adviceTypeFieldOptions" :key="option.id" :value="option.value">
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="advice_type_virtual_option_value">
                                    <FormItem>
                                        <FormLabel class="text-xs">Option für "Virtuell" *</FormLabel>
                                        <Select v-model="formDefinition.advice_mapping.advice_type_virtual_option_value">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Option auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="option in adviceTypeFieldOptions" :key="option.id" :value="option.value">
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </FormItem>
                                </FormField>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- MapPoint Mapping Card -->
            <Card>
                <CardContent class="space-y-4" v-if="formDefinition.map_point_mapping !== null">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold">Kartenpunkt erstellen</h3>
                            <Badge
                                :variant="
                                    mapPointValidation.status === 'complete'
                                        ? 'default'
                                        : mapPointValidation.status === 'incomplete'
                                          ? 'destructive'
                                          : 'secondary'
                                "
                                class="flex items-center gap-1"
                            >
                                <CheckCircle v-if="mapPointValidation.status === 'complete'" class="h-3 w-3" />
                                <XCircle v-else-if="mapPointValidation.status === 'incomplete'" class="h-3 w-3" />
                                <span v-if="mapPointValidation.status === 'complete'">Vollständig</span>
                                <span v-else-if="mapPointValidation.status === 'incomplete'">Unvollständig</span>
                                <span v-else>Deaktiviert</span>
                            </Badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="formDefinition.map_point_mapping.enabled" />
                            <span class="text-sm">Aktiv</span>
                        </div>
                    </div>

                    <div v-if="formDefinition.map_point_mapping.enabled" class="space-y-4">
                        <!-- Warnings -->
                        <div v-if="mapPointValidation.warnings.length > 0" class="rounded-md border border-red-200 bg-red-50 p-4">
                            <div class="flex">
                                <AlertTriangle class="h-4 w-4 text-red-600" />
                                <div class="ml-3">
                                    <ul class="list-inside list-disc space-y-1 text-sm text-red-800">
                                        <li v-for="warning in mapPointValidation.warnings" :key="warning">{{ warning }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <FormField v-slot="{ componentField }" name="title_field_id">
                                <FormItem>
                                    <FormLabel>Titel *</FormLabel>
                                    <Select v-model="formDefinition.map_point_mapping.title_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in textFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="description_field_id">
                                <FormItem>
                                    <FormLabel>Beschreibung *</FormLabel>
                                    <Select v-model="formDefinition.map_point_mapping.description_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in textareaFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="coordinate_field_id">
                                <FormItem>
                                    <FormLabel>Koordinaten *</FormLabel>
                                    <Select v-model="formDefinition.map_point_mapping.coordinate_field_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Feld auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="field in geoCoordinateFields" :key="field.id" :value="field.id">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormItem>
                            </FormField>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
