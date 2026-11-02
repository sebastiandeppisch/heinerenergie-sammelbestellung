<script setup lang="ts">
import HexNetCellInfo from '@/components/HexNet/HexNetCellInfo.vue';
import HexNetLogo from '@/components/HexNet/HexNetLogo.vue';
import HexNetMap from '@/components/HexNet/HexNetMap.vue';
import HexNetPointInfo from '@/components/HexNet/HexNetPointInfo.vue';
import HexNetScoreboard from '@/components/HexNet/HexNetScoreboard.vue';
import HexNetViewControls from '@/components/HexNet/HexNetViewControls.vue';
import { BASEMAPS, type Basemap, type HexCells, levelFor, type MarkerStyle } from '@/components/HexNet/hexnet';
import NoLayout from '@/layouts/NoLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ChevronDown, Info, Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

defineOptions({ layout: NoLayout });

const props = defineProps<{
    hexNet: App.HexNet.Data.HexNetPublicData;
    cells: HexCells;
    counts: Record<string, number>;
    stats: App.HexNet.Data.HexNetStatsData;
    points: App.HexNet.Data.HexNetPointData[];
    measures: App.Data.MapPointCharacteristicData[];
    measureCounts: Record<string, Record<string, number>>;
}>();

const thresholds = computed(() => ({ medium: props.hexNet.threshold_medium, dark: props.hexNet.threshold_dark }));
const scoreOpen = ref(false);

const measureId = ref<string | null>(null);
const measure = computed(() => props.measures.find((m) => m.id === measureId.value) ?? null);
const mapMeasure = computed(() => (measure.value ? { id: measure.value.id, color: measure.value.color ?? '#1f7a34' } : null));
const basemap = ref<Basemap>(validBasemap(stored('hexnet.basemap', 'soft')));
const markerStyle = ref<MarkerStyle>(stored('hexnet.markerStyle', 'card') as MarkerStyle);
watch(basemap, (value) => store('hexnet.basemap', value));
watch(markerStyle, (value) => store('hexnet.markerStyle', value));

/** The score of the shown measure, built like the overall score from the counts per cell. */
const shownStats = computed<App.HexNet.Data.HexNetStatsData>(() => {
    if (!measure.value) return props.stats;
    const counts = Object.values(props.measureCounts[measure.value.id] ?? {});
    const levels = [0, 0, 0, 0];
    counts.forEach((count) => levels[levelFor(count, thresholds.value)]++);
    const green = levels[1] + levels[2] + levels[3];
    levels[0] = Math.max(0, props.stats.playable_cells - green);
    return {
        percent: props.stats.playable_cells ? Math.round((100 * green) / props.stats.playable_cells) : 0,
        playable_cells: props.stats.playable_cells,
        levels,
        total_points: counts.reduce((a, b) => a + b, 0),
    };
});

function contactUrl(entry: App.HexNet.Data.HexNetPointData): string {
    return route('hexnet.contact.create', [props.hexNet.slug, entry.point.id]);
}

function validBasemap(value: string): Basemap {
    return value in BASEMAPS ? (value as Basemap) : 'soft';
}

function stored(key: string, fallback: string): string {
    try {
        return localStorage.getItem(key) ?? fallback;
    } catch {
        return fallback;
    }
}

function store(key: string, value: string) {
    try {
        localStorage.setItem(key, value);
    } catch {
        // per-viewer convenience only
    }
}
</script>

<template>
    <Head :title="hexNet.title" />
    <div class="hexnet-app">
        <aside class="hexnet-side">
            <div class="brand">
                <div class="tag">Point of Insects</div>
                <h1>{{ hexNet.title }}</h1>
                <p v-if="hexNet.subtitle">{{ hexNet.subtitle }}</p>
                <div v-if="hexNet.is_draft" class="draft">Vorschau – dieses HexaNetz ist noch ein Entwurf</div>
            </div>

            <button class="mobile-score" @click="scoreOpen = !scoreOpen">
                <span class="pct">{{ shownStats.percent }} %</span>
                <span class="track-mini">
                    <i :style="{ width: shownStats.percent + '%', background: measure?.color ?? undefined }" />
                </span>
                <span class="sum">{{ shownStats.total_points }} Trittsteine</span>
                <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': scoreOpen }" />
            </button>

            <div class="details" :class="{ open: scoreOpen }">
                <div v-if="measure" class="measure-head" :style="{ '--c': measure.color ?? '#1f7a34' }">
                    Spielstand nur für <b>{{ measure.name }}</b>
                    <button @click="measureId = null">alle zeigen</button>
                </div>
                <HexNetScoreboard :stats="shownStats" :thresholds="thresholds" :color="measure?.color ?? null" />
                <HexNetViewControls v-model:measure="measureId" v-model:basemap="basemap" v-model:marker-style="markerStyle" :measures="measures" />
                <div class="join">
                    <h2>Mitmachen</h2>
                    <p v-if="hexNet.cta_text" class="note">{{ hexNet.cta_text }}</p>
                    <a v-if="hexNet.form_url" :href="hexNet.form_url" class="act">Trittstein anmelden</a>
                </div>
                <details class="about">
                    <summary><Info class="h-3.5 w-3.5" /> Über diese Karte</summary>
                    <div class="about-body">
                        <HexNetLogo class="about-logo" />
                        <p>
                            Die Karte ist mit <b>HexaNetz</b> gebaut, einem Kampagnen-Baustein der Plattform heiner*energie. Waben werden aus den
                            gemeldeten Trittsteinen berechnet, einzelne Gärten sind nur ungefähr verortet.
                        </p>
                        <p>Ihr wollt so eine Kampagne für eure Initiative? Sprecht uns an.</p>
                    </div>
                </details>
            </div>
        </aside>

        <main class="hexnet-main">
            <HexNetMap
                :cells="cells"
                :counts="counts"
                :thresholds="thresholds"
                :points="points"
                :bounds="hexNet.bounds"
                :measure="mapMeasure"
                :measure-counts="measureCounts"
                :marker-style="markerStyle"
                :basemap="basemap"
            >
                <template #cell="{ cell }">
                    <HexNetCellInfo
                        :cell="cell"
                        :thresholds="thresholds"
                        :form-url="hexNet.form_url"
                        :measure-name="measure?.name"
                        :measure-color="measure?.color"
                    />
                </template>
                <template #point="{ point }">
                    <HexNetPointInfo :entry="point" :contact-url="contactUrl(point)" />
                </template>
            </HexNetMap>
            <a v-if="hexNet.form_url" :href="hexNet.form_url" class="fab"><Plus class="h-5 w-5" /> Trittstein anmelden</a>
        </main>
    </div>
</template>

<style scoped>
.hexnet-app {
    display: flex;
    height: 100vh;
    height: 100dvh;
    color: #16211c;
    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        Roboto,
        sans-serif;
    font-size: 13px;
    -webkit-font-smoothing: antialiased;
}
.hexnet-side {
    width: 348px;
    flex: 0 0 348px;
    background: #fff;
    border-right: 1px solid #dde5da;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    z-index: 1000;
}
.hexnet-main {
    flex: 1;
    position: relative;
}
.brand {
    padding: 16px 18px 14px;
    border-bottom: 1px solid #dde5da;
}
.tag {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: #e8720c;
    text-transform: uppercase;
}
h1 {
    margin: 4px 0 0;
    font-size: 19px;
    font-weight: 700;
    letter-spacing: -0.02em;
}
.brand p {
    margin: 4px 0 0;
    font-size: 11.5px;
    color: #7d8d85;
}
.draft {
    margin-top: 10px;
    padding: 6px 8px;
    border-radius: 6px;
    background: #fbe3cd;
    color: #a24f06;
    font-size: 11.5px;
    font-weight: 600;
}
.measure-head {
    display: flex;
    gap: 6px;
    align-items: center;
    padding: 8px 18px;
    background: color-mix(in srgb, var(--c) 14%, #fff);
    border-bottom: 1px solid #dde5da;
    font-size: 12px;
}
.measure-head button {
    margin-left: auto;
    border: 0;
    background: none;
    color: #46564e;
    text-decoration: underline;
    cursor: pointer;
    font-size: 11.5px;
}
.join {
    padding: 15px 18px;
    border-bottom: 1px solid #dde5da;
}
.join h2 {
    margin: 0 0 11px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #7d8d85;
}
.note {
    margin: 0 0 11px;
    font-size: 11.5px;
    color: #46564e;
    line-height: 1.5;
}
.act {
    display: block;
    text-align: center;
    font-size: 12.5px;
    font-weight: 600;
    padding: 9px 13px;
    background: #e8720c;
    border-radius: 6px;
    color: #fff;
}
.act:hover {
    background: #cf6408;
}
.about {
    margin-top: auto;
    padding: 10px 18px 14px;
    font-size: 11px;
    color: #7d8d85;
}
.about summary {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    list-style: none;
}
.about summary::-webkit-details-marker {
    display: none;
}
.about-body {
    margin-top: 8px;
    line-height: 1.5;
}
.about-body p {
    margin: 0 0 6px;
}
.about-logo {
    width: 110px;
    margin-bottom: 6px;
}
.mobile-score,
.fab {
    display: none;
}

@media (max-width: 820px) {
    .hexnet-app {
        flex-direction: column;
    }
    .hexnet-side {
        width: 100%;
        flex: 0 0 auto;
        max-height: 62%;
        border-right: 0;
        border-bottom: 1px solid #dde5da;
    }
    .brand {
        padding: 10px 14px;
    }
    .brand p,
    .tag {
        display: none;
    }
    h1 {
        font-size: 16px;
        margin: 0;
    }
    .mobile-score {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        background: #f1f5f0;
        border: 0;
        border-bottom: 1px solid #dde5da;
        font: inherit;
        text-align: left;
    }
    .pct {
        font-weight: 700;
        font-size: 18px;
        color: #1f7a34;
    }
    .track-mini {
        flex: 1;
        height: 8px;
        border-radius: 4px;
        background: #dfe6dc;
        overflow: hidden;
    }
    .track-mini i {
        display: block;
        height: 100%;
        background: #52b45a;
    }
    .sum {
        font-size: 11.5px;
        color: #46564e;
    }
    .details {
        display: none;
    }
    .details.open {
        display: block;
    }
    .details .act {
        display: none;
    }
    .hexnet-main {
        flex: 1;
        min-height: 0;
    }
    .fab {
        position: absolute;
        right: 14px;
        bottom: 22px;
        z-index: 1050;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 12px 18px;
        border-radius: 999px;
        background: #e8720c;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 6px 18px rgba(232, 114, 12, 0.45);
    }
}
</style>
