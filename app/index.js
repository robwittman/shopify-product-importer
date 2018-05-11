import React from 'react';
import { render } from "react-dom";
import { Provider } from "react-redux";
import { addArticle } from "./actions/index";
import LoginForm from './components/LoginForm';
import { Route } from 'react-router'
import createHistory from 'history/createBrowserHistory'

import { ConnectedRouter, routerReducer, routerMiddleware } from 'react-router-redux'
import rootReducer from './reducers/index'
import { createStore, combineReducers, applyMiddleware } from 'redux'
import api from './reducers/api'

const history = createHistory();
const middleware = routerMiddleware(history);

const store = createStore(
    combineReducers({
        app: rootReducer,
        router: routerReducer
    }),
    applyMiddleware(middleware, api)
);

window.store = store;
window.addArticle = addArticle;

render(
    <Provider store={store}>
        <ConnectedRouter history={history}>
            <div>
                <Route exact path="/" component={LoginForm}/>
            </div>
        </ConnectedRouter>
    </Provider>,
    document.getElementById("app")
);