export const getError = state => type => {
    if(state.dataError !== undefined && state.dataError[type] !== undefined){
        if(typeof state.dataError[type] === "object"){
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};

/**
 * получить номер жевого билета
 *
 * @param state
 * @returns {boolean}
 */
export const getLiveTicketNumber = state => {
    return state.live.number;
};
