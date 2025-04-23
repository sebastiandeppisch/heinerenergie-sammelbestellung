import './bootstrap';
import '../css/app.css';
//import 'devextreme/dist/css/dx.common.css';
import './themes/generated/theme.base.css';
import './themes/generated/theme.additional.css';
import { createApp, h, DefineComponent } from 'vue'
import { ZiggyVue } from 'ziggy-js';

import { createInertiaApp } from '@inertiajs/vue3'

import library from './fontawesome'
library.add()

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import SideNavOuterToolbar from './layouts/SideNavOuterToolbar.vue';

// ApexCharts importieren
//import ApexChartsPlugin from './plugins/apexcharts';
// ApexCharts CSS
import VueApexCharts from 'vue3-apexcharts';
import 'apexcharts/dist/apexcharts.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: name => {
		const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue', { eager: true })
		const page = pages[`./Pages/${name}.vue`]
		if (!page) {
			throw new Error(`Page ${name} not found`)
		}
		page.default.layout = page.default.layout || SideNavOuterToolbar
		return page
	},
	setup({ el, App, props, plugin }) {
		const app = createApp({ render: () => h(App, props) })
		app.use(plugin)
		app.use(ZiggyVue)
		app.use(VueApexCharts)
		//		app.use(ApexChartsPlugin) // ApexCharts Plugin registrieren
		app.mount(el)
		app.component('font-awesome-icon', FontAwesomeIcon)
	},
})