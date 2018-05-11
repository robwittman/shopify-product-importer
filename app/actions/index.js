import { ADD_ARTICLE, SUBMIT_LOGIN } from "../constants/action-types";

export const addArticle = article => ({
    type: ADD_ARTICLE,
    payload: article
});

export const login = creds => ({
    type: SUBMIT_LOGIN,
    payload: creds
});