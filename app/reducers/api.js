import request from 'superagent'

const dataService = store => next => action => {
    /*
    Pass all actions through by default
    */
    next(action)
    switch (action.type) {
        case 'SUBMIT_LOGIN':
            /*
            In case we receive an action to send an API request, send the appropriate request
            */
            request
                .post('http://127.0.0.1/auth/token')
                .send(action.payload)
                .end((err, res) => {
                    if (err) {
                        /*
                        in case there is any error, dispatch an action containing the error
                        */
                        return next({
                            type: 'LOGIN_ERROR',
                            err
                        })
                    }
                    console.log(res);
                    const data = JSON.parse(res);
                    /*
                    Once data is received, dispatch an action telling the application
                    that data was received successfully, along with the parsed data
                    */
                    next({
                        type: 'LOGIN_SUCCESSFUL',
                        data
                    })
                });
            break;
        /*
        Do nothing if the action does not interest us
        */
        default:
            break
    }

};

export default dataService