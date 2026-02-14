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

export const setMessage = (state, payload) => {
    state.message = payload;
};

export const approve = (state, payload) => {
    if (state.questionnaireList && Array.isArray(state.questionnaireList)) {
        const item = state.questionnaireList.find(item => item.id === payload.id);
        if (item) {
            item.status = 'APPROVE';
        }
    }
    if (state.questionnaireItem && state.questionnaireItem.id === payload.id) {
        state.questionnaireItem.status = 'APPROVE';
    }
};