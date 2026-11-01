<script setup lang="ts">
import HexNetCellInfo from '@/components/HexNet/HexNetCellInfo.vue';
import HexNetLogo from '@/components/HexNet/HexNetLogo.vue';
import HexNetMap from '@/components/HexNet/HexNetMap.vue';
import HexNetPointInfo from '@/components/HexNet/HexNetPointInfo.vue';
import HexNetScoreboard from '@/components/HexNet/HexNetScoreboard.vue';
import type { HexCells } from '@/components/HexNet/hexnet';
import NoLayout from '@/layouts/NoLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ChevronDown, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';

defineOptions({ layout: NoLayout });

const props = defineProps<{
    hexNet: App.HexNet.Data.HexNetPublicData;
    cells: HexCells;
    counts: Record<string, number>;
    stats: App.HexNet.Data.HexNetStatsData;
    points: App.HexNet.Data.HexNetPointData[];
}>();

const thresholds = computed(() => ({ medium: props.hexNet.threshold_medium, dark: props.hexNet.threshold_dark }));
const scoreOpen = ref(false);
</script>

<template>
    <Head :title="hexNet.title" />
    <div class="hexnet-app">
        <aside class="hexnet-side">
            <div class="brand">
                <div class="brand-row">
                    <div>
                        <div class="tag">Point of Insects</div>
                        <h1>{{ hexNet.title }}</h1>
                        <p v-if="hexNet.subtitle">{{ hexNet.subtitle }}</p>
                    </div>
                    <HexNetLogo :wordmark="false" class="logo" />
                </div>
                <div v-if="hexNet.is_draft" class="draft">Vorschau – dieses HexaNetz ist noch ein Entwurf</div>
            </div>

            <button class="mobile-score" @click="scoreOpen = !scoreOpen">
                <span class="pct">{{ stats.percent }} %</span>
                <span class="track-mini">
                    <i :style="{ width: stats.percent + '%' }" />
                </span>
                <span class="sum">{{ stats.total_points }} Trittsteine</span>
                <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': scoreOpen }" />
            </button>

            <div class="details" :class="{ open: scoreOpen }">
                <HexNetScoreboard :stats="stats" :thresholds="thresholds" />
                <div class="join">
                    <h2>Mitmachen</h2>
                    <p v-if="hexNet.cta_text" class="note">{{ hexNet.cta_text }}</p>
                    <a v-if="hexNet.form_url" :href="hexNet.form_url" class="act">Trittstein anmelden</a>
                </div>
                <div class="powered">
                    <HexNetLogo class="h-10 w-auto" />
                </div>
            </div>
        </aside>

        <main class="hexnet-main">
            <HexNetMap :cells="cells" :counts="counts" :thresholds="thresholds" :points="points" :bounds="hexNet.bounds">
                <template #cell="{ cell }">
                    <HexNetCellInfo :cell="cell" :thresholds="thresholds" :form-url="hexNet.form_url" />
                </template>
                <template #point="{ point }">
                    <HexNetPointInfo :entry="point" />
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
.brand-row {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    align-items: flex-start;
}
.logo {
    width: 72px;
    flex: 0 0 72px;
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
.powered {
    margin-top: auto;
    padding: 18px;
    display: flex;
    justify-content: center;
    opacity: 0.85;
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
    .logo,
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
    .details .act,
    .powered {
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
