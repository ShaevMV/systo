import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// Дашборд админки — READ-ONLY сводные метрики qr-заказов (заказы + выручка в разрезах).
// Источник данных — POST /api/v1/qrOrder/getStats.
export default {
    namespaced: true,
    state: {
        stats: {
            totals: { orders: 0, revenue: 0 },
            byStatus: [],
            byType: [],
            timeseries: []
        },
        filter: {},
        isLoading: false,
        dataError: []
    },
    getters,
    actions,
    mutations
};
