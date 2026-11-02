<script setup lang="ts">
import HexNetMap from '@/components/HexNet/HexNetMap.vue';
import { DEFAULT_LEVEL_COLORS, type HexCells } from '@/components/HexNet/hexnet';
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Label } from '@/shadcn/components/ui/label';
import { Switch } from '@/shadcn/components/ui/switch';
import { useForm } from '@inertiajs/vue3';
import { Lock, LockOpen } from '@lucide/vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    hexNet: App.HexNet.Data.HexNetData;
    minRadius: number;
    maxRadius: number;
}>();

const emit = defineEmits<{ saved: [] }>();

const form = useForm({
    hex_radius_m: props.hexNet.hex_radius_m,
    grid_locked: props.hexNet.grid_locked,
    threshold_medium: props.hexNet.threshold_medium,
    threshold_dark: props.hexNet.threshold_dark,
    jitter_m: props.hexNet.jitter_m,
    color_open: props.hexNet.level_colors[0],
    color_light: props.hexNet.level_colors[1],
    color_medium: props.hexNet.level_colors[2],
    color_dark: props.hexNet.level_colors[3],
    confirm_radius_change: false,
});

const preview = ref<{ cells: HexCells; counts: Record<string, number>; count: number; cell_area_ha: number } | null>(null);
const previewError = ref<string | null>(null);
const confirmOpen = ref(false);
const radiusLocked = computed(() => props.hexNet.grid_locked);
const radiusChanged = computed(() => form.hex_radius_m !== props.hexNet.hex_radius_m);
const levelFields = [
    { key: 'color_open', label: 'offen' },
    { key: 'color_light', label: 'hell' },
    { key: 'color_medium', label: 'mittel' },
    { key: 'color_dark', label: 'dunkel' },
] as const;
const levelColors = computed(() => [form.color_open, form.color_light, form.color_medium, form.color_dark]);

function resetColors() {
    [form.color_open, form.color_light, form.color_medium, form.color_dark] = DEFAULT_LEVEL_COLORS;
}

let timer: ReturnType<typeof setTimeout> | undefined;
let requestId = 0;

async function loadPreview() {
    if (!props.hexNet.play_area) return;
    const id = ++requestId;
    try {
        const { data } = await axios.post(route('hexnets.grid-preview', props.hexNet.id), { hex_radius_m: form.hex_radius_m });
        if (id !== requestId) return;
        preview.value = data;
        previewError.value = null;
    } catch (error) {
        if (id !== requestId) return;
        preview.value = null;
        previewError.value = axios.isAxiosError(error) ? (error.response?.data?.message ?? 'Vorschau nicht möglich') : 'Vorschau nicht möglich';
    }
}

watch(
    () => form.hex_radius_m,
    () => {
        clearTimeout(timer);
        timer = setTimeout(loadPreview, 250);
    },
);
watch(
    () => form.threshold_medium,
    (medium) => {
        if (form.threshold_dark <= medium) form.threshold_dark = medium + 1;
    },
);
onMounted(loadPreview);

function save(confirmed = false) {
    if (radiusChanged.value && props.hexNet.status === 'active' && !confirmed) {
        confirmOpen.value = true;
        return;
    }
    form.confirm_radius_change = confirmed;
    confirmOpen.value = false;
    form.put(route('hexnets.update-step', [props.hexNet.id, 'grid']), { preserveScroll: true, onSuccess: () => emit('saved') });
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
        <div class="space-y-5">
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <Label for="radius">Wabenradius</Label>
                    <span class="font-mono text-sm font-semibold text-green-800">{{ form.hex_radius_m }} m</span>
                </div>
                <input
                    id="radius"
                    v-model.number="form.hex_radius_m"
                    type="range"
                    :min="minRadius"
                    :max="maxRadius"
                    step="10"
                    class="w-full accent-green-800"
                    :disabled="radiusLocked"
                />
                <p v-if="preview" class="text-xs text-muted-foreground">
                    Spielstand mit diesem Radius:
                    <b>{{ Math.round((100 * Object.keys(preview.counts).length) / Math.max(preview.count, 1)) }} %</b> ·

                    {{ preview.cell_area_ha.toLocaleString('de-DE') }} ha je Wabe · {{ preview.count.toLocaleString('de-DE') }} Waben im Spielfeld ·
                    bis zu {{ 2 * form.hex_radius_m }} m zwischen zwei Trittsteinen derselben Wabe
                </p>
                <p v-if="form.errors.hex_radius_m" class="text-sm text-red-500">{{ form.errors.hex_radius_m }}</p>
                <p v-if="form.errors.confirm_radius_change" class="text-sm text-red-500">{{ form.errors.confirm_radius_change }}</p>
            </div>

            <div class="flex items-center justify-between rounded-md border p-3">
                <div class="flex items-center gap-2 text-sm">
                    <component :is="form.grid_locked ? Lock : LockOpen" class="h-4 w-4" />
                    Raster sperren
                </div>
                <Switch v-model="form.grid_locked" />
            </div>
            <p class="-mt-3 text-xs text-muted-foreground">
                Gesperrt lassen sich Radius und Versatz nicht ändern. Zum Ändern erst entsperren und speichern.
            </p>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <Label for="medium">Schwelle Mittelgrün</Label>
                    <span class="font-mono text-sm font-semibold text-green-800">{{ form.threshold_medium }}</span>
                </div>
                <input id="medium" v-model.number="form.threshold_medium" type="range" min="2" max="6" class="w-full accent-green-800" />
            </div>
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <Label for="dark">Schwelle Dunkelgrün</Label>
                    <span class="font-mono text-sm font-semibold text-green-800">{{ form.threshold_dark }}</span>
                </div>
                <input
                    id="dark"
                    v-model.number="form.threshold_dark"
                    type="range"
                    :min="form.threshold_medium + 1"
                    max="10"
                    class="w-full accent-green-800"
                />
                <p class="text-xs text-muted-foreground">Zusammen mit dem Radius die eigentliche Spielbalance.</p>
                <p v-if="form.errors.threshold_dark" class="text-sm text-red-500">{{ form.errors.threshold_dark }}</p>
            </div>
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <Label for="jitter">Standort verwischen</Label>
                    <span class="font-mono text-sm font-semibold text-green-800">{{ form.jitter_m }} m</span>
                </div>
                <input
                    id="jitter"
                    v-model.number="form.jitter_m"
                    type="range"
                    min="20"
                    max="150"
                    step="10"
                    class="w-full accent-green-800"
                    :disabled="hexNet.grid_locked"
                />
                <p v-if="form.errors.jitter_m" class="text-sm text-red-500">{{ form.errors.jitter_m }}</p>
                <p class="text-xs text-muted-foreground">
                    Ein Punkt auf einem Privatgarten ist eine Wohnadresse. Der Versatz hält die Adresse unscharf.
                </p>
            </div>

            <div class="space-y-2">
                <Label>Farben der Stufen</Label>
                <div class="grid grid-cols-4 gap-2">
                    <label v-for="level in levelFields" :key="level.key" class="flex flex-col items-center gap-1 text-xs text-muted-foreground">
                        <input
                            v-model="form[level.key]"
                            type="color"
                            class="h-9 w-full cursor-pointer rounded border"
                            :aria-label="`Farbe ${level.label}`"
                        />
                        {{ level.label }}
                    </label>
                </div>
                <button type="button" class="text-xs text-muted-foreground underline" @click="resetColors">Standardfarben</button>
            </div>

            <Button :disabled="form.processing" @click="save()">Speichern</Button>
        </div>

        <div class="h-[480px] overflow-hidden rounded-lg border">
            <HexNetMap
                v-if="preview"
                :cells="preview.cells"
                :counts="preview.counts"
                :thresholds="{ medium: form.threshold_medium, dark: form.threshold_dark }"
                :interactive="false"
                :level-colors="levelColors"
            />
            <div v-else class="flex h-full items-center justify-center p-6 text-center text-sm text-muted-foreground">
                {{ previewError ?? (hexNet.play_area ? 'Vorschau wird geladen …' : 'Zeichne zuerst das Spielfeld.') }}
            </div>
        </div>
    </div>

    <Dialog v-model:open="confirmOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Radius einer laufenden Kampagne ändern?</DialogTitle>
                <DialogDescription>
                    Alle Waben werden neu berechnet. Spielstand und Prozentzahl ändern sich sofort, Teilnehmende finden ihre Wabe ggf. an anderer
                    Stelle.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmOpen = false">Abbrechen</Button>
                <Button variant="destructive" @click="save(true)">Radius ändern</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
