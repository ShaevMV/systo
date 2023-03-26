export const getError = state => type => {
    if(state.dataError !== undefined && state.dataError[type] !== undefined){
        if(typeof state.dataError[type] === "object"){
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};


export const getPromoCodeList = state => {
    return state.promoCodeList;
}

export const getPromoCodeItem = state => {
    return state.promoCodeItem;
}