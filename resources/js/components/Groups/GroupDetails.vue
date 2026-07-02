<template>
    <div class="group-details pt-6">
        <div class="mb-4 flex justify-end">
            <Button v-if="canEdit" variant="ghost" @click="confirmDelete">
                <Trash2 class="h-4 w-4" />
                Löschen
            </Button>
        </div>
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Group name -->
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" :disabled="!canEdit" :class="{ 'border-destructive': form.errors.name }" />
                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
            </div>

            <!-- Group description -->
            <div class="space-y-2">
                <Label for="description">Beschreibung</Label>
                <Textarea
                    id="description"
                    v-model="formDescription"
                    class="min-h-[100px]"
                    :disabled="!canEdit"
                    :class="{ 'border-destructive': form.errors.description }"
                />
                <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
            </div>

            <!-- Group URL -->
            <div class="space-y-2">
                <Label for="url">URL</Label>
                <Input
                    id="url"
                    v-model="formUrl"
                    type="url"
                    :disabled="!canEdit"
                    placeholder="https://..."
                    :class="{ 'border-destructive': form.errors.url }"
                />
                <div v-if="form.errors.url" class="text-sm text-red-500">{{ form.errors.url }}</div>
            </div>

            <!-- Group logo -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Logo</label>
                <div class="flex items-center">
                    <img :src="logoSrc" :alt="group.name" class="mr-4 h-12 max-w-12 rounded object-contain" />
                    <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="handleLogoChange" />
                    <Button type="button" variant="outline" @click="logoInput?.click()" v-if="canEdit">
                        <Upload class="h-4 w-4" />
                        Logo auswählen
                    </Button>
                    <Button type="button" v-if="logoSrc && !form.remove_logo && canEdit" variant="outline" class="ml-2" @click="removeLogo">
                        <X class="h-4 w-4" />
                        Logo Entfernen
                    </Button>
                </div>
                <div v-if="form.errors.logo" class="text-sm text-red-500">{{ form.errors.logo }}</div>
            </div>

            <!-- Group marker -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Berater:in Kartenmarker</label>
                <div class="flex items-center">
                    <img :src="markerSrc" :alt="group.name + ' Marker'" class="mr-4 h-12 max-w-12 rounded object-contain" />
                    <input type="file" ref="markerInput" class="hidden" accept="image/*" @change="handleMarkerChange" />
                    <Button type="button" variant="outline" @click="markerInput?.click()" v-if="canEdit">
                        <Upload class="h-4 w-4" />
                        Berater:in Kartenmarker auswählen
                    </Button>
                    <Button type="button" v-if="markerSrc && !form.remove_marker && canEdit" variant="outline" class="ml-2" @click="removeMarker">
                        <X class="h-4 w-4" />
                        Marker Entfernen
                    </Button>
                </div>
                <div v-if="form.errors.marker" class="text-sm text-red-500">{{ form.errors.marker }}</div>
            </div>

            <div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="accepts_transfers"
                        v-model="form.accepts_transfers"
                        :disabled="!canEdit"
                    />
                    <label for="accepts_transfers" class="text-sm font-medium leading-none">
                        Beratungen von anderen Initiativen akzeptieren
                    </label>
                </div>
                <div v-if="form.errors.accepts_transfers" class="text-sm text-red-500">{{ form.errors.accepts_transfers }}</div>
            </div>

            <!-- Nextcloud group name -->
            <div class="space-y-2">
                <Label for="nextcloud_group_name">Nextcloud-Gruppenname</Label>
                <Input
                    id="nextcloud_group_name"
                    v-model="formNextcloudGroupName"
                    :disabled="!canEdit"
                    placeholder="z. B. Initiative-Hamburg"
                    :class="{ 'border-destructive': form.errors.nextcloud_group_name }"
                />
                <div v-if="form.errors.nextcloud_group_name" class="text-sm text-red-500">{{ form.errors.nextcloud_group_name }}</div>
            </div>

            <!-- Primary color -->
            <div class="space-y-3">
                <Label>Primärfarbe (Corporate Design)</Label>

                <Tabs :default-value="initialTab">
                    <TabsList class="grid w-full grid-cols-2">
                        <TabsTrigger value="simple">Einfach</TabsTrigger>
                        <TabsTrigger value="expert">Expert-Modus</TabsTrigger>
                    </TabsList>

                    <!-- Simple mode: color picker -->
                    <TabsContent value="simple" class="mt-3 space-y-3">
                        <p class="text-xs text-muted-foreground">
                            Die gewählte Farbe wird für optimale Lesbarkeit und Barrierefreiheit leicht angepasst (Helligkeit und Sättigung werden
                            normiert). Im
                            <span class="font-medium">Expert-Modus</span> kannst du alle Werte manuell einstellen.
                        </p>
                        <div class="flex items-end gap-4">
                            <div v-if="canEdit" class="flex flex-col items-center gap-1">
                                <input
                                    id="primary_color"
                                    type="color"
                                    v-model="pickerHex"
                                    class="h-10 w-10 cursor-pointer rounded border border-input bg-transparent p-0.5"
                                />
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="h-10 w-10 rounded border border-input" :style="{ backgroundColor: previewColorLight }" />
                                <span class="text-xs text-muted-foreground">Hell</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="flex h-10 w-10 items-center justify-center rounded border border-input bg-zinc-900">
                                    <div class="h-6 w-6 rounded" :style="{ backgroundColor: previewColorDark }" />
                                </div>
                                <span class="text-xs text-muted-foreground">Dunkel</span>
                            </div>
                            <span class="mb-0.5 text-sm text-muted-foreground">
                                {{ form.primary_hue != null ? Math.round(form.primary_hue) + '°' : 'Standard' }}
                            </span>
                            <Button v-if="canEdit && form.primary_hue != null" type="button" variant="ghost" size="sm" @click="resetColor">
                                Zurücksetzen
                            </Button>
                        </div>
                    </TabsContent>

                    <!-- Expert mode: sliders + number inputs -->
                    <TabsContent value="expert" class="mt-3 space-y-4 rounded-md border border-input p-4">
                        <p class="text-xs text-muted-foreground">
                            Die Farbe wird im
                            <a href="https://oklch.com" target="_blank" rel="noopener" class="underline underline-offset-2">OKLCH-Farbraum</a>
                            definiert
                        </p>
                        <div class="space-y-2">
                            <Label for="primary_hue">Hue (Farbton)</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="primary_hue"
                                    type="range"
                                    min="0"
                                    max="360"
                                    step="0.1"
                                    :value="form.primary_hue ?? 47.604"
                                    :disabled="!canEdit"
                                    @input="form.primary_hue = parseFloat(($event.target as HTMLInputElement).value)"
                                    class="flex-1 accent-primary"
                                    :style="{ backgroundImage: hueGradient }"
                                />
                                <Input
                                    type="number"
                                    min="0"
                                    max="360"
                                    step="0.1"
                                    :model-value="form.primary_hue ?? 47.604"
                                    :disabled="!canEdit"
                                    @update:model-value="(v) => (form.primary_hue = v != null ? parseFloat(String(v)) : null)"
                                    class="w-24"
                                />
                            </div>
                            <div v-if="form.errors.primary_hue" class="text-sm text-red-500">{{ form.errors.primary_hue }}</div>
                        </div>

                        <div class="space-y-2">
                            <Label for="primary_lightness">Lightness (Helligkeit)</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="primary_lightness"
                                    type="range"
                                    min="0"
                                    max="1"
                                    step="0.001"
                                    :value="form.primary_lightness ?? 0.705"
                                    :disabled="!canEdit"
                                    @input="form.primary_lightness = parseFloat(($event.target as HTMLInputElement).value)"
                                    class="flex-1"
                                />
                                <Input
                                    type="number"
                                    min="0"
                                    max="1"
                                    step="0.001"
                                    :model-value="form.primary_lightness ?? 0.705"
                                    :disabled="!canEdit"
                                    @update:model-value="(v) => (form.primary_lightness = v != null ? parseFloat(String(v)) : null)"
                                    class="w-24"
                                />
                            </div>
                            <div v-if="form.errors.primary_lightness" class="text-sm text-red-500">{{ form.errors.primary_lightness }}</div>
                        </div>

                        <div class="space-y-2">
                            <Label for="primary_chroma">Chroma (Sättigung)</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="primary_chroma"
                                    type="range"
                                    min="0"
                                    max="0.4"
                                    step="0.001"
                                    :value="form.primary_chroma ?? 0.213"
                                    :disabled="!canEdit"
                                    @input="form.primary_chroma = parseFloat(($event.target as HTMLInputElement).value)"
                                    class="flex-1"
                                />
                                <Input
                                    type="number"
                                    min="0"
                                    max="0.4"
                                    step="0.001"
                                    :model-value="form.primary_chroma ?? 0.213"
                                    :disabled="!canEdit"
                                    @update:model-value="(v) => (form.primary_chroma = v != null ? parseFloat(String(v)) : null)"
                                    class="w-24"
                                />
                            </div>
                            <div v-if="form.errors.primary_chroma" class="text-sm text-red-500">{{ form.errors.primary_chroma }}</div>
                        </div>

                        <div class="flex items-end gap-4">
                            <div class="flex flex-col items-center gap-1">
                                <div class="h-10 w-10 rounded border border-input" :style="{ backgroundColor: previewColorLight }" />
                                <span class="text-xs text-muted-foreground">Hell</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="flex h-10 w-10 items-center justify-center rounded border border-input bg-zinc-900">
                                    <div class="h-6 w-6 rounded" :style="{ backgroundColor: previewColorDark }" />
                                </div>
                                <span class="text-xs text-muted-foreground">Dunkel</span>
                            </div>
                            <Button v-if="canEdit" type="button" variant="ghost" size="sm" @click="resetColor"> Zurücksetzen </Button>
                        </div>
                    </TabsContent>
                </Tabs>

                <p class="text-xs text-muted-foreground">
                    Wähle eine Primärfarbe für das Corporate Design dieser Initiative. Betrifft Buttons, Links und andere Akzentelemente.
                </p>
            </div>

            <div class="flex justify-end">
                <Button type="button" v-if="canEdit" variant="default" @click="handleSubmit">
                    <Save class="h-4 w-4" />
                    Speichern
                </Button>
            </div>
        </form>
    </div>

    <Dialog v-model:open="showDeleteConfirm">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Initiative löschen</DialogTitle>
                <DialogDescription>Soll diese Initiative wirklich gelöscht werden?</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteConfirm = false">Abbrechen</Button>
                <Button variant="destructive" @click="doDelete">Löschen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { Save, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watchEffect } from 'vue';
import { route } from 'ziggy-js';

function hexToHue(hex: string): number {
    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const delta = max - min;
    if (delta === 0) {
        return 0;
    }
    let h = 0;
    if (max === r) {
        h = ((g - b) / delta) % 6;
    } else if (max === g) {
        h = (b - r) / delta + 2;
    } else {
        h = (r - g) / delta + 4;
    }
    return Math.round((h * 60 + 360) % 360);
}

function hslToHex(h: number, s: number, l: number): string {
    const a = s * Math.min(l, 1 - l);
    const f = (n: number) => {
        const k = (n + h / 30) % 12;
        const color = l - a * Math.max(-1, Math.min(k - 3, Math.min(9 - k, 1)));
        return Math.round(255 * color)
            .toString(16)
            .padStart(2, '0');
    };
    return `#${f(0)}${f(8)}${f(4)}`;
}

type GroupData = App.Data.GroupData;

const props = defineProps<{
    group: GroupData;
    canEdit: boolean;
}>();

const logoSrc = computed(() => {
    if (form.logo) {
        return URL.createObjectURL(form.logo);
    }

    if (props.group.logo_path) {
        return props.group.logo_path;
    }

    return '/img/example_img.svg';
});

const markerSrc = computed(() => {
    if (form.marker) {
        return URL.createObjectURL(form.marker);
    }

    if (props.group.marker_path) {
        return props.group.marker_path;
    }

    return '/images/markers/he_yellow.svg';
});

type FormData = Omit<
    GroupData,
    'id' | 'logo_path' | 'marker_path' | 'parent_id' | 'users_count' | 'advices_count' | 'userCanActAsAdmin' | 'new_advice_mail'
> & {
    logo: File | null;
    marker: File | null;
    remove_logo: boolean;
    remove_marker: boolean;
    _method: string;
};

const form = useForm<FormData>({
    name: props.group.name,
    description: props.group.description,
    url: props.group.url ?? null,
    accepts_transfers: props.group.accepts_transfers,
    nextcloud_group_name: props.group.nextcloud_group_name,
    primary_hue: props.group.primary_hue ?? null,
    primary_lightness: props.group.primary_lightness ?? null,
    primary_chroma: props.group.primary_chroma ?? null,
    logo: null,
    marker: null,
    remove_logo: false,
    remove_marker: false,
    _method: 'PUT',
});

function applyColorVars(h: number | null, l: number | null, c: number | null) {
    const el = document.documentElement;
    if (h != null) el.style.setProperty('--primary-hue', String(h));
    else el.style.removeProperty('--primary-hue');
    if (l != null) el.style.setProperty('--primary-lightness', String(l));
    else el.style.removeProperty('--primary-lightness');
    if (c != null) el.style.setProperty('--primary-chroma', String(c));
    else el.style.removeProperty('--primary-chroma');
}

watchEffect(() => applyColorVars(form.primary_hue, form.primary_lightness, form.primary_chroma));

onUnmounted(() => applyColorVars(props.group.primary_hue ?? null, props.group.primary_lightness ?? null, props.group.primary_chroma ?? null));

const initialTab = props.group.primary_lightness != null || props.group.primary_chroma != null ? 'expert' : 'simple';

const previewColorLight = computed(() => {
    const h = form.primary_hue ?? 47.604;
    const l = form.primary_lightness ?? 0.705;
    const c = form.primary_chroma ?? 0.213;
    return `oklch(${l} ${c} ${h})`;
});

const previewColorDark = computed(() => {
    const h = form.primary_hue ?? 47.604;
    return `oklch(0.646 0.222 ${h})`;
});

const pickerHex = computed({
    get: () => hslToHex(form.primary_hue ?? 47.604, 0.8, 0.55),
    set: (hex: string) => {
        form.primary_hue = hexToHue(hex);
    },
});

const hueGradient = computed(() => {
    const stops = Array.from({ length: 13 }, (_, i) => `hsl(${i * 30}, 80%, 55%) ${i * (100 / 12)}%`).join(', ');
    return `linear-gradient(to right, ${stops})`;
});

const resetColor = () => {
    form.primary_hue = null;
    form.primary_lightness = null;
    form.primary_chroma = null;
};

// Computed property to handle null to undefined conversion for description
const formDescription = computed({
    get: () => form.description ?? undefined,
    set: (value) => (form.description = value || null),
});

// Computed property to handle null to undefined conversion for url
const formUrl = computed({
    get: () => form.url ?? undefined,
    set: (value) => (form.url = value || null),
});

const formNextcloudGroupName = computed({
    get: () => form.nextcloud_group_name ?? undefined,
    set: (value) => (form.nextcloud_group_name = value || null),
});

const logoInput = ref<HTMLInputElement | null>(null);
const markerInput = ref<HTMLInputElement | null>(null);

const handleLogoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
    }
};

const handleMarkerChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.marker = file;
        form.remove_marker = false;
    }
};

const removeLogo = () => {
    form.logo = null;
    if (logoInput.value) {
        logoInput.value.value = '';
    }
    form.remove_logo = true;
};

const removeMarker = () => {
    form.marker = null;
    if (markerInput.value) {
        markerInput.value.value = '';
    }
    form.remove_marker = true;
};

const handleSubmit = () => {
    form.post(route('groups.update', props.group.id), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const showDeleteConfirm = ref(false);

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const doDelete = () => {
    showDeleteConfirm.value = false;
    form.delete(route('groups.destroy', props.group.id), {
        preserveScroll: true,
    });
};
</script>
