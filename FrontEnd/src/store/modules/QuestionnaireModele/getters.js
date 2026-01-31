export const getError = state => type => {
    if (state.dataError !== undefined && state.dataError[type] !== undefined) {
        if (typeof state.dataError[type] === "object") {
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};

/**
 * Вывести список всех анкет
 *
 * @param state
 * @returns {Array}
 */
export const getQuestionnaireList = state => {
    return state.questionnaireList;
};


/**
 * Вывести анкету
 *
 * @param state
 * @returns {null| Object}
 */
export const getQuestionnaireItem = state => {
    return state.questionnaireItem;
};