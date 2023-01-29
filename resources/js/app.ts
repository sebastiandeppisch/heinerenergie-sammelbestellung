require('./bootstrap');
//import 'devextreme/dist/css/dx.common.css';
import './themes/generated/theme.base.css';
import './themes/generated/theme.additional.css';
import { createApp }  from "vue";
import router from "./router";

import App from "./App.vue";
import appInfo from "./app-info";
import { store, key } from './store';

import library from './fontawesome'
library.add()

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import auth from './auth';

const app = createApp(App);
app.component('font-awesome-icon', FontAwesomeIcon)
app.use(store, key);
app.use(router);
app.config.globalProperties.$appInfo = appInfo;
app.mount('#app');

auth.initLogin();