import { ADD_ARTICLE, SUBMIT_LOGIN } from "../constants/action-types";
const initialState = {
    articles: []
};
const rootReducer = (state = initialState, action) => {
    switch (action.type) {
        case ADD_ARTICLE:
            return { ...state, articles: [...state.articles, action.payload] };

        case SUBMIT_LOGIN:
            console.log(action);
        default:
            return state;
    }
};
export default rootReducer;