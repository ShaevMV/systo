export const setError = (state, payload) => {
    state.dataError = payload;
};

/**
 * Записать список всех анкет
 *
 * @param state
 * @param payload
 */
export const setQuestionnaireList = (state, payload) => {
    state.questionnaireList = payload;
};

/**
 * Записать данные поределённой анкеты
 */
export const setQuestionnaireItem = (state, payload) => {
    state.questionnaireItem = payload;
};