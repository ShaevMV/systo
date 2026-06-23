export const getList = (state) => state.list;

export const getItem = (state) => state.item;

export const getHistory = (state) => state.history;

export const isLoading = (state) => state.isLoading;

export const isHistoryLoading = (state) => state.historyLoading;

/** Первая ошибка валидации поля `data.<field>` (формат Laravel 422). */
export const getError = (state) => (field) => {
    const key = 'data.' + field;
    const raw = state.dataError?.[key];
    if (raw === undefined) return '';
    return Array.isArray(raw) ? raw[0] : raw;
};
