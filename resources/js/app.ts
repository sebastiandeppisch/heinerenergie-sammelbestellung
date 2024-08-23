import './bootstrap';
//import 'devextreme/dist/css/dx.common.css';
import './themes/generated/theme.base.css';
import './themes/generated/theme.additional.css';
import { createApp, h, DefineComponent } from 'vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import { store, key } from './store';
import { createInertiaApp } from '@inertiajs/vue3'


import library from './fontawesome'
library.add()

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import auth from './auth';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
	setup({ el, App, props, plugin }) {
		const app = createApp({ render: () => h(App, props) })
		app.use(plugin)
		app.mount(el)
		//TODO add Ziggy

		app.component('font-awesome-icon', FontAwesomeIcon)
		app.use(store, key);
		//app.use(router);

		auth.initLogin(); //TODO refactor auth management
	},
})