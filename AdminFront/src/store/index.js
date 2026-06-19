import { createStore } from 'vuex';

import appUser from './modules/UserModule/index';
import appQrOrder from './modules/QrOrderModule/index';
import appFestivalTickets from './modules/FestivalTicketsModule/index';
import appDashboard from './modules/DashboardModule/index';
import appTemplate from './modules/TemplateModule/index';
import appTemplateBinding from './modules/TemplateBindingModule/index';
import appEmailDelivery from './modules/EmailDeliveryModule/index';
import appBazaDelivery from './modules/BazaDeliveryModule/index';

// Доменный стор админки (Vuex). Layout-состояние Sakai живёт отдельно —
// в composable src/layout/composables/layout.js (НЕ Pinia, НЕ здесь).
export default createStore({
    modules: {
        appUser,
        appQrOrder,
        appFestivalTickets,
        appDashboard,
        appTemplate,
        appTemplateBinding,
        appEmailDelivery,
        appBazaDelivery
    }
});
