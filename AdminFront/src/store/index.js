import { createStore } from 'vuex';

import appUser from './modules/UserModule/index';
import appQrOrder from './modules/QrOrderModule/index';
import appFestivalTickets from './modules/FestivalTicketsModule/index';

// Доменный стор админки (Vuex). Layout-состояние Sakai живёт отдельно —
// в composable src/layout/composables/layout.js (НЕ Pinia, НЕ здесь).
export default createStore({
    modules: {
        appUser,
        appQrOrder,
        appFestivalTickets
    }
});
