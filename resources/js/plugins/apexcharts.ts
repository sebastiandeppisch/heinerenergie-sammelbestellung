import { App } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

export default {
    install: (app: App): void => {
        app.component('apexchart', VueApexCharts);
    },
};
