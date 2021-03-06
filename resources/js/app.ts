require('./bootstrap');
//import 'devextreme/dist/css/dx.common.css';
import './themes/generated/theme.base.css';
import './themes/generated/theme.additional.css';
import { createApp }  from "vue";
import router from "./router";

import App from "./App.vue";
import appInfo from "./app-info";
import { store, key } from './store';

const app = createApp(App);
app.use(store, key);
app.use(router);
app.config.globalProperties.$appInfo = appInfo;
app.mount('#app');
