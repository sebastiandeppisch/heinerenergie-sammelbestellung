import VueApexCharts from 'vue3-apexcharts';
import { App } from 'vue';

export default {
	install: (app: App): void => {
		app.component('apexchart', VueApexCharts);
	}
}; 