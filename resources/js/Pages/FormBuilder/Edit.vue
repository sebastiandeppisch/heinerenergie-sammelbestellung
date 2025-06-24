<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import FormBuilderCanvas from '@/components/FormBuilder/FormBuilderCanvas.vue';
import FormBuilderToolbox from '@/components/FormBuilder/FormBuilderToolbox.vue';
import FormBuilderProperties from '@/components/FormBuilder/FormBuilderProperties.vue';
import FormBuilderPreview from '@/components/FormBuilder/FormBuilderPreview.vue';
import FormEmbedDialog from '@/components/FormBuilder/FormEmbedDialog.vue';
import { nanoid } from 'nanoid';
import { route } from 'ziggy-js';
import { toast } from 'vue-sonner'
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/shadcn/components/ui/tabs';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Form, FormField, FormItem, FormLabel, FormControl, FormDescription } from '@/shadcn/components/ui/form';
import { Input } from '@/shadcn/components/ui/input';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { v4 as uuidv4 } from 'uuid';
import { ArrowUpRightFromSquare } from 'lucide-vue-next';
type FormDefinitionData = App.Data.FormDefinitionData;
type FormFieldData = App.Data.FormFieldData;
type FieldType = App.Enums.FieldType;
type FormFieldOptionData = App.Data.FormFieldOptionData;

const props = defineProps<{
    formDefinition: FormDefinitionData | null;
    fieldTypes: FieldType[];
    isEdit: boolean;
}>();


const formDefinition = reactive<FormDefinitionData>(props.formDefinition || {
    id: uuidv4(),
    name: 'Neues Formular',
    description: null,
    is_active: true,
    fields: []
});

const selectedField = ref<FormFieldData | null>(null);

const selectedTab = ref('canvas');

function handleFieldSelect(field: FormFieldData | null) {
    selectedField.value = field;
    console.log('Selected field Edit:', field);
}

function handleFieldsUpdate(fields: FormFieldData[]) {
    formDefinition.fields = fields;
}

function handleFieldChange(field: FormFieldData) {
    const index = formDefinition.fields.findIndex(f => f.id === field.id);
    if (index !== -1) {
        formDefinition.fields[index] = field;
    }
}

function saveForm() {
    if (props.isEdit) {
        router.put(route('form-definitions.update', formDefinition.id), formDefinition, {
            onSuccess: () => {
                toast({
                    title: 'Gespeichert',
                    description: 'Formular wurde erfolgreich gespeichert',
                    variant: 'success'
                });
            },
            onError: (errors) => {
                console.log('errors', errors);
                toast({
                    title: 'Fehler',
                    description: `Fehler beim Speichern des Formulars: ${Object.values(errors).join(', ')}`,
                    variant: 'destructive'
                });
            }
        });
    } else {
        router.post(route('form-definitions.store'), formDefinition, {
            onSuccess: () => {
                toast({
                    title: 'Erstellt',
                    description: 'Formular wurde erfolgreich erstellt',
                    variant: 'success'
                });
            },
            onError: (errors) => {
                console.log('errors', errors);
                toast({
                    title: 'Fehler',
                    description: `Fehler beim Erstellen des Formulars: ${Object.values(errors).join(', ')}`,
                    variant: 'destructive'
                });
            }
        });
    }
}

function goBack() {
    router.visit(route('form-definitions.index'));
}

function createField(type: FieldType): FormFieldData {
    const field: FormFieldData = {
        id: uuidv4(),
        placeholder: null,
        help_text: null,
        default_value: null,
        min_length: null,
        max_length: null,
        min_value: null,
        max_value: null,
        accepted_file_types: [],
        type: type,
        label: `${type.toString()} Feld`,
        required: false,
        sort_order: formDefinition.fields.length,
        options: [],
    };

    switch (type) {
        case 'select':
        case 'radio':
            field.options = [
                {
                    id: uuidv4(),
                    label: 'Option 1',
                    value: 'option1',
                    sort_order: 0,
                    is_default: false,
                },
                {
                    id: uuidv4(),
                    label: 'Option 2',
                    value: 'option2',
                    sort_order: 1,
                    is_default: false,
                }
            ];
            break;
        case 'checkbox':
            field.options = [
                {
                    id: uuidv4(),
                    label: 'Option',
                    value: 'option',
                    sort_order: 0,
                    is_default: false,
                }
            ];
            break;
        case 'file':
            field.accepted_file_types = ['.jpg', '.png', '.pdf'];
            break;
    }

    return field;
}

function addField(type: FieldType) {
    const newField = createField(type);
    formDefinition.fields.push(newField);
    selectedField.value = newField;
    selectedTab.value = 'canvas';
}


function openFormular() {
    if (props.formDefinition?.id) {
        window.open(route('form.show', props.formDefinition?.id), '_blank');
    }
}

const nullsafeDescription = computed<string>({
    get(){
        return formDefinition.description || '';
    },
    set(value: string){
        formDefinition.description = value;
    }
});
</script>

<template>
    <div class="form-builder">
        <div class="form-builder__toolbar">
            <Button @click="goBack" variant="outline">Zurück zur Übersicht</Button>

            <div class="form-builder__toolbar">
                <FormEmbedDialog :form-definition="props.formDefinition" v-if="props.isEdit && props.formDefinition !== null" />
                <Button @click="openFormular" variant="outline" v-if="props.isEdit">
                    Formular öffnen
                    <ArrowUpRightFromSquare />
                </Button>
                <Button @click="saveForm" variant="default">Speichern</Button>
            </div>
        </div>

        <Card class="form-builder__header">
            <CardContent>
                <Form class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField v-slot="{ componentField }" name="name">
                        <FormItem>
                            <FormLabel>Name</FormLabel>
                            <FormControl>
                                <Input v-model="formDefinition.name" />
                            </FormControl>
                        </FormItem>
                    </FormField>
                    <FormField v-slot="{ componentField }" name="description">
                        <FormItem>
                            <FormLabel>Beschreibung</FormLabel>
                            <FormControl>
                                <Textarea v-model="nullsafeDescription" />
                            </FormControl>
                        </FormItem>
                    </FormField>
                    <FormField v-slot="{ componentField }" name="is_active">
                        <FormItem class="flex flex-row items-center space-x-2 space-y-0">
                            <FormControl>
                                <Checkbox v-model="formDefinition.is_active" />
                            </FormControl>
                            <FormLabel>Aktiv</FormLabel>
                        </FormItem>
                    </FormField>
                </Form>
            </CardContent>
        </Card>

        <Tabs v-model="selectedTab" class="form-builder__content">
            <TabsList>
                <TabsTrigger value="canvas">Canvas</TabsTrigger>
                <TabsTrigger value="preview">Vorschau</TabsTrigger>
            </TabsList>

            <TabsContent value="canvas">
                <div class="flex flex-col items-center">
                    <div class="form-builder__canvas-container">
                        <FormBuilderToolbox :field-types="fieldTypes" @add-field="addField" class="form-builder__toolbox" />
                    <FormBuilderCanvas :modelValue="formDefinition.fields" @update:model-value="handleFieldsUpdate"
                            @field-selected="handleFieldSelect" class="form-builder__canvas" />


                        <FormBuilderProperties
                            v-model="selectedField"
                            v-if="selectedField"
                            class="form-builder__properties"
                        />

                    </div>
                </div>
            </TabsContent>

            <TabsContent value="preview">
                <FormBuilderPreview
                    v-if="props.formDefinition !== null" :form-definition="props.formDefinition"
                    class="form-builder__preview"
                />
            </TabsContent>
        </Tabs>
    </div>
</template>

<style scoped>
.form-builder {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 20px;
    gap: 20px;
}

.form-builder__toolbar {
    display: flex;
    gap: 10px;
    justify-content: space-between;
}

.form-builder__header {
    background: #f8f8f8;
    padding: 15px;
    border-radius: 4px;
}

.form-builder__content {
    flex-grow: 1;
}

.form-builder__canvas-container {
    display: grid;
    grid-template-columns: 200px 300px 1fr;
    gap: 20px;
    height: 100%;
    max-width: 1200px;
}

.form-builder__toolbox,
.form-builder__canvas,
.form-builder__properties,
.form-builder__preview {
    background: #f8f8f8;
    padding: 15px;
    border-radius: 4px;
    overflow: auto;
}

.form-builder__toolbox {
    grid-column: 1;
}

.form-builder__canvas {
    grid-column: 2;
}

.form-builder__properties {
    grid-column: 3;
}

.form-builder__preview {
    height: 100%;
}
</style>
