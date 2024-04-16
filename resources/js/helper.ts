import {loadMessages, locale } from 'devextreme/localization'
import german from 'devextreme/localization/messages/de.json'

loadMessages(german);
locale('de');
import config from "devextreme/core/config";
 
config({
    defaultCurrency: 'EUR'
});
import './themes/generated/theme.base.css';
import './themes/generated/theme.additional.css';
import { createApp }  from "vue";

import appInfo from "./app-info";


import library from './fontawesome'
library.add()

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import NewAdvice from './views/NewAdvice.vue'

const newAdvice = createApp(NewAdvice);
newAdvice.component('font-awesome-icon', FontAwesomeIcon)

newAdvice.config.globalProperties.$appInfo = appInfo;
newAdvice.mount('#new-advice');