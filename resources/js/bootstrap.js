/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
import axios from 'axios';
import { loadMessages, locale } from 'devextreme/localization';
import german from 'devextreme/localization/messages/de.json';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

loadMessages(german);
locale('de');

import config from 'devextreme/core/config';

config({
    defaultCurrency: 'EUR',
});
