import config from 'devextreme/core/config';
import { loadMessages, locale } from 'devextreme/localization';
import german from 'devextreme/localization/messages/de.json';
import { createApp } from 'vue';
import './themes/generated/theme.additional.css';
import './themes/generated/theme.base.css';

loadMessages(german);
locale('de');

config({
    defaultCurrency: 'EUR',
});

import library from './fontawesome';
library.add();

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import NewAdvice from './Pages/NewAdvice.vue';

const newAdvice = createApp(NewAdvice);
newAdvice.component('font-awesome-icon', FontAwesomeIcon);

newAdvice.mount('#new-advice');
