export const setError = (state, payload) => {
    state.dataError = payload;
};
/**
 * Записать номер
 *
 * @param state
 * @param payload
 */
export const setLiveNumber = (state, payload) => {
    state.live.number = payload;
};

