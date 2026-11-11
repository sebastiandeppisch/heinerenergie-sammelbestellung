<script setup lang="ts">
import HexNetLogo from '@/components/HexNet/HexNetLogo.vue';
import StepCategories from '@/components/HexNet/Wizard/StepCategories.vue';
import StepGrid from '@/components/HexNet/Wizard/StepGrid.vue';
import { SCORING_MODES } from '@/components/HexNet/hexnet';
import PageHeader from '@/components/PageHeader.vue';
import PolygonMap from '@/components/PolygonMap.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Stepper, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/shadcn/components/ui/stepper';
import { Switch } from '@/shadcn/components/ui/switch';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Check, ChevronLeft, ChevronRight, CircleAlert, ExternalLink, FileText, Pencil, Rocket, Sparkles } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type Step = 'basics' | 'texts' | 'categories' | 'play-area' | 'grid' | 'form' | 'launch';

const props = defineProps<{
    hexNet: App.HexNet.Data.HexNetData | null;
    step: Step;
    steps: Step[];
    checklist: Record<string, boolean>;
    measureCount: number;
    groups: Array<App.Data.GroupBaseData>;
    categories: Array<App.Data.MapPointCategoryData>;
    forms: Array<{ id: string; name: string }>;
    defaults: {
        main_category: string;
        sub_category: string;
        measures: Array<{ name: string; color: string; icon: string }>;
        icons: Array<{ key: string; url: string }>;
        min_radius: number;
        max_radius: number;
    };
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Kampagnen', href: route('hexnets.index') }, { title: props.hexNet?.name ?? 'Neues HexaNetz' }],
});

const STEP_META: Record<Step, { title: string; description: string }> = {
    basics: { title: 'Grunddaten', description: 'Name, Adresse der Seite und die Initiative, der das HexaNetz gehört.' },
    texts: { title: 'Texte', description: 'Was Besucher*innen in der Seitenleiste lesen.' },
    categories: { title: 'Kategorien & Maßnahmen', description: 'Welche Kartenpunkte als Trittsteine zählen.' },
    'play-area': { title: 'Spielfeld', description: 'Das Gebiet, das grün werden soll. Waben zählen, wenn ihr Mittelpunkt darin liegt.' },
    grid: { title: 'Raster & Spielregeln', description: 'Größe der Waben, Schwellen für die Grünstufen und Datenschutz.' },
    form: { title: 'Mitmach-Formular', description: 'Darüber melden Menschen ihren Trittstein an.' },
    launch: { title: 'Überblick & Start', description: 'Prüfen und die Kampagne starten.' },
};

const current = computed<Step>(() => (props.hexNet ? props.step : 'basics'));
const currentIndex = computed(() => props.steps.indexOf(current.value));
const nextStep = computed(() => props.steps[currentIndex.value + 1] ?? null);
const prevStep = computed(() => props.steps[currentIndex.value - 1] ?? null);

function go(step: Step) {
    if (!props.hexNet) return;
    router.visit(route('hexnets.edit', { hexNet: props.hexNet.id, step }), { preserveScroll: false });
}

function saveStep(step: Step, form: ReturnType<typeof useForm>, advance = true) {
    if (!props.hexNet) return;
    form.transform((data) => ({ ...data, next: advance ? nextStep.value : null })).put(route('hexnets.update-step', [props.hexNet.id, step]), {
        preserveScroll: true,
        preserveState: !advance,
    });
}

const basics = useForm({
    name: props.hexNet?.name ?? '',
    slug: props.hexNet?.slug ?? '',
    group_id: props.hexNet?.group_id ?? props.groups[0]?.id ?? '',
});
const slugTouched = ref(!!props.hexNet);
watch(
    () => basics.name,
    (name) => {
        if (!slugTouched.value) basics.slug = slugify(name);
    },
);

function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/ä/g, 'ae')
        .replace(/ö/g, 'oe')
        .replace(/ü/g, 'ue')
        .replace(/ß/g, 'ss')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}

function submitBasics() {
    if (props.hexNet) {
        saveStep('basics', basics);
    } else {
        basics.post(route('hexnets.store'), { preserveState: false });
    }
}

const texts = useForm({
    title: props.hexNet?.title ?? '',
    subtitle: props.hexNet?.subtitle ?? '',
    cta_text: props.hexNet?.cta_text ?? '',
});

const playArea = useForm({ play_area: props.hexNet?.play_area ?? { coordinates: [] } });

const formStep = useForm({ form_definition_id: props.hexNet?.form_definition_id ?? null, auto_publish: props.hexNet?.auto_publish ?? false });
const creatingForm = ref(false);

function createForm() {
    if (!props.hexNet) return;
    creatingForm.value = true;
    router.post(route('hexnets.setup-form', props.hexNet.id), {}, { preserveScroll: true, onFinish: () => (creatingForm.value = false) });
}

watch(
    () => props.hexNet?.form_definition_id,
    (id) => (formStep.form_definition_id = id ?? null),
);

const launch = useForm({ status: props.hexNet?.status ?? 'draft' });

function setStatus(status: 'draft' | 'active') {
    launch.status = status;
    saveStep('launch', launch, false);
}

const allDone = computed(() => Object.values(props.checklist).every(Boolean));
</script>

<template>
    <div class="mx-auto w-full max-w-6xl">
        <PageHeader :title="hexNet ? hexNet.name : 'Neues HexaNetz'" :description="STEP_META[current].description">
            <template #actions>
                <HexNetLogo class="hidden h-14 w-auto sm:block" />
                <Button v-if="hexNet" as="a" :href="hexNet.public_url" target="_blank" variant="outline">
                    <ExternalLink class="mr-2 h-4 w-4" /> Vorschau
                </Button>
            </template>
        </PageHeader>

        <Stepper
            :model-value="currentIndex + 1"
            :linear="false"
            class="mb-6 flex w-full items-start gap-2"
            data-test="hexnet-stepper"
            @update:model-value="(value) => value && value !== currentIndex + 1 && go(steps[value - 1])"
        >
            <StepperItem
                v-for="(step, index) in steps"
                :key="step"
                v-slot="{ state }"
                :step="index + 1"
                class="relative flex w-full flex-col items-center justify-center"
                :disabled="!hexNet && index > 0"
                :completed="!!hexNet && index < currentIndex"
            >
                <StepperSeparator
                    v-if="index !== steps.length - 1"
                    class="absolute top-5 right-[calc(-50%+10px)] left-[calc(50%+20px)] block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-green-700"
                />
                <StepperTrigger as-child>
                    <Button
                        :variant="state === 'inactive' ? 'outline' : 'default'"
                        size="icon"
                        class="z-10 shrink-0 rounded-full"
                        :class="[
                            state === 'completed' && 'bg-green-700 hover:bg-green-800',
                            state === 'active' && 'ring-2 ring-green-700 ring-offset-2',
                        ]"
                        :disabled="!hexNet && index > 0"
                    >
                        <Check v-if="state === 'completed'" class="size-5" />
                        <span v-else class="text-xs">{{ index + 1 }}</span>
                    </Button>
                </StepperTrigger>
                <div class="mt-2 flex flex-col items-center text-center">
                    <StepperTitle :class="[state === 'active' && 'text-primary']" class="text-xs font-semibold transition lg:text-sm">
                        {{ STEP_META[step].title }}
                    </StepperTitle>
                </div>
            </StepperItem>
        </Stepper>

        <Card>
            <CardHeader>
                <CardTitle>{{ STEP_META[current].title }}</CardTitle>
                <CardDescription>{{ STEP_META[current].description }}</CardDescription>
            </CardHeader>
            <CardContent>
                <form v-if="current === 'basics'" class="grid max-w-xl gap-4" @submit.prevent="submitBasics">
                    <div class="space-y-1">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="basics.name" placeholder="Darmstadt wird grün 2027" />
                        <p v-if="basics.errors.name" class="text-sm text-red-500">{{ basics.errors.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label for="slug">Adresse der öffentlichen Seite</Label>
                        <div class="flex items-center gap-1 text-sm text-muted-foreground">
                            <span>/hexanetz/</span>
                            <Input id="slug" v-model="basics.slug" @input="slugTouched = true" />
                        </div>
                        <p v-if="basics.errors.slug" class="text-sm text-red-500">{{ basics.errors.slug }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Initiative</Label>
                        <Select v-model="basics.group_id">
                            <SelectTrigger><SelectValue placeholder="Initiative wählen" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-muted-foreground">Untergruppen sehen das HexaNetz, Nachbargruppen nicht.</p>
                        <p v-if="basics.errors.group_id" class="text-sm text-red-500">{{ basics.errors.group_id }}</p>
                    </div>
                    <div>
                        <Button type="submit" :disabled="basics.processing">{{ hexNet ? 'Speichern & weiter' : 'Entwurf anlegen' }}</Button>
                    </div>
                </form>

                <div v-else-if="current === 'texts'" class="grid gap-6 lg:grid-cols-[1fr_348px]">
                    <form class="grid gap-4" @submit.prevent="saveStep('texts', texts)">
                        <div class="space-y-1">
                            <Label for="title">Titel</Label>
                            <Input id="title" v-model="texts.title" />
                            <p v-if="texts.errors.title" class="text-sm text-red-500">{{ texts.errors.title }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label for="subtitle">Untertitel</Label>
                            <Input id="subtitle" v-model="texts.subtitle" />
                        </div>
                        <div class="space-y-1">
                            <Label for="cta">Mitmach-Text</Label>
                            <Textarea id="cta" v-model="texts.cta_text" rows="5" />
                        </div>
                        <div><Button type="submit" :disabled="texts.processing">Speichern & weiter</Button></div>
                    </form>
                    <div class="rounded-lg border bg-white text-[13px] shadow-sm">
                        <div class="border-b p-4">
                            <div class="text-[10.5px] font-bold tracking-widest text-[#e8720c] uppercase">Point of Insects</div>
                            <div class="mt-1 text-[19px] font-bold tracking-tight">{{ texts.title || 'Titel' }}</div>
                            <div class="mt-1 text-[11.5px] text-[#7d8d85]">{{ texts.subtitle }}</div>
                        </div>
                        <div class="border-b bg-[#f1f5f0] p-4">
                            <div class="text-4xl font-bold text-[#1f7a34]">{{ hexNet?.stats?.percent ?? 0 }}<span class="text-xl"> %</span></div>
                            <div class="mt-1 text-xs text-[#46564e]">der bespielbaren Fläche hat mindestens einen Trittstein</div>
                        </div>
                        <div class="p-4">
                            <p class="mb-3 text-[11.5px] leading-relaxed text-[#46564e]">{{ texts.cta_text }}</p>
                            <div class="rounded-md bg-[#e8720c] py-2 text-center text-xs font-semibold text-white">Trittstein anmelden</div>
                        </div>
                    </div>
                </div>

                <StepCategories v-else-if="current === 'categories' && hexNet" :hex-net="hexNet" :categories="categories" :defaults="defaults" />

                <div v-else-if="current === 'play-area'" class="space-y-4">
                    <div class="h-[520px] overflow-hidden rounded-lg border">
                        <PolygonMap v-model="playArea.play_area" class="h-full" :logo="null" :logo-aspect="null" />
                    </div>
                    <p v-if="playArea.errors['play_area.coordinates']" class="text-sm text-red-500">
                        Zeichne ein Gebiet mit mindestens drei Punkten.
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Der Ursprung des Rasters wird beim ersten Speichern auf die Mitte des Spielfelds gesetzt und bleibt danach fest.
                    </p>
                    <Button :disabled="playArea.processing" @click="saveStep('play-area', playArea)">Speichern & weiter</Button>
                </div>

                <StepGrid
                    v-else-if="current === 'grid' && hexNet"
                    :hex-net="hexNet"
                    :min-radius="defaults.min_radius"
                    :max-radius="defaults.max_radius"
                    :measure-count="measureCount"
                />

                <div v-else-if="current === 'form' && hexNet" class="grid max-w-2xl gap-6">
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <div class="flex items-center gap-2 font-semibold"><Sparkles class="h-4 w-4" /> Formular aus Vorlage erzeugen</div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Titel, Beschreibung, Pin, E-Mail, Maßnahmen (mindestens eine), Ortsbeschreibung, „Ich möchte eine Stadtnaturberatung“,
                            Einwilligung und E-Mail-Bestätigung.
                        </p>
                        <Button class="mt-3" :disabled="creatingForm || hexNet.category_ids.length === 0" @click="createForm"
                            >Formular erzeugen</Button
                        >
                        <p v-if="hexNet.category_ids.length === 0" class="mt-2 text-xs text-red-600">Lege zuerst die Kategorien an.</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Oder ein vorhandenes Kartenpunkt-Formular wählen</Label>
                        <Select v-model="formStep.form_definition_id">
                            <SelectTrigger><SelectValue placeholder="Formular wählen" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="form in forms" :key="form.id" :value="form.id">{{ form.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="formStep.errors.form_definition_id" class="text-sm text-red-500">{{ formStep.errors.form_definition_id }}</p>
                    </div>
                    <div class="flex items-start justify-between gap-4 rounded-md border p-3">
                        <div>
                            <Label for="auto_publish">Neue Trittsteine sofort veröffentlichen</Label>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Sonst erscheinen sie erst nach Freigabe durch euch auf der Karte. Gezählt werden sie immer, sichtbar sind sie nur mit
                                Einwilligung.
                            </p>
                        </div>
                        <Switch id="auto_publish" v-model="formStep.auto_publish" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button :disabled="formStep.processing" @click="saveStep('form', formStep)">Speichern & weiter</Button>
                        <Button
                            v-if="hexNet.form_definition_id"
                            variant="outline"
                            as="a"
                            :href="route('form-definitions.edit', hexNet.form_definition_id)"
                        >
                            <Pencil class="mr-2 h-4 w-4" /> Im Formular-Editor öffnen
                        </Button>
                        <Button
                            v-if="hexNet.form_definition_id"
                            variant="outline"
                            as="a"
                            :href="route('form.show', hexNet.form_definition_id)"
                            target="_blank"
                        >
                            <FileText class="mr-2 h-4 w-4" /> Formular ansehen
                        </Button>
                    </div>
                </div>

                <div v-else-if="current === 'launch' && hexNet" class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-3">
                        <div v-for="(done, label) in checklist" :key="label" class="flex items-center gap-2 text-sm">
                            <Check v-if="done" class="h-4 w-4 text-green-700" />
                            <CircleAlert v-else class="h-4 w-4 text-orange-600" />
                            {{ label }}
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            <dt class="text-muted-foreground">Status</dt>
                            <dd>{{ hexNet.status_label }}</dd>
                            <dt class="text-muted-foreground">Waben im Spielfeld</dt>
                            <dd>{{ hexNet.stats?.playable_cells ?? 0 }}</dd>
                            <dt class="text-muted-foreground">Trittsteine</dt>
                            <dd>{{ hexNet.stats?.total_points ?? 0 }}</dd>
                            <dt class="text-muted-foreground">Spielstand</dt>
                            <dd>{{ hexNet.stats?.percent ?? 0 }} %</dd>
                            <dt class="text-muted-foreground">Radius / Schwellen</dt>
                            <dd>
                                {{ hexNet.hex_radius_m }} m · {{ hexNet.threshold_medium }} / {{ hexNet.threshold_dark }}
                                {{ SCORING_MODES[hexNet.scoring_mode].label }}
                            </dd>
                            <dt class="text-muted-foreground">Veröffentlichung</dt>
                            <dd>{{ hexNet.auto_publish ? 'sofort' : 'nach Freigabe' }}</dd>
                            <dt class="text-muted-foreground">Formular</dt>
                            <dd>{{ hexNet.form_definition_name ?? '–' }}</dd>
                        </dl>
                    </div>
                    <div class="space-y-3">
                        <p v-if="launch.errors.status" class="text-sm text-red-500">{{ launch.errors.status }}</p>
                        <Button
                            v-if="hexNet.status === 'draft'"
                            size="lg"
                            class="w-full"
                            :disabled="!allDone || launch.processing"
                            @click="setStatus('active')"
                        >
                            <Rocket class="mr-2 h-4 w-4" /> Kampagne starten
                        </Button>
                        <Button v-else size="lg" variant="outline" class="w-full" :disabled="launch.processing" @click="setStatus('draft')">
                            Zurück auf Entwurf setzen
                        </Button>
                        <Button variant="outline" class="w-full" as="a" :href="hexNet.public_url" target="_blank">
                            <ExternalLink class="mr-2 h-4 w-4" /> Öffentliche Seite ansehen
                        </Button>
                        <Button variant="ghost" class="w-full" @click="router.visit(route('hexnets.index'))">Zur Kampagnen-Übersicht</Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div v-if="hexNet" class="mt-4 flex justify-between">
            <Button v-if="prevStep" variant="ghost" @click="go(prevStep)"
                ><ChevronLeft class="mr-1 h-4 w-4" /> {{ STEP_META[prevStep].title }}</Button
            >
            <span v-else />
            <Button v-if="nextStep" variant="ghost" @click="go(nextStep)"
                >{{ STEP_META[nextStep].title }} <ChevronRight class="ml-1 h-4 w-4"
            /></Button>
        </div>
    </div>
</template>
