import React, { Component } from "react";
import { connect } from "react-redux";
import { login} from "../actions";

const mapDispatchToProps = dispatch => {
    return {
        login: creds => dispatch(login(creds))
    };
};

class ConnectedLoginForm extends Component {
    constructor() {
        super();
        this.state = {
            email: "",
            password: ""
        };
        this.handleChange = this.handleChange.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleChange(event) {
        this.setState({ [event.target.id]: event.target.value });
    }

    handleSubmit(event) {
        event.preventDefault();
        const { email, password } = this.state;
        this.props.login({email, password});
        this.setState({ email: email, password: ""});
    }

    render() {
        const { email, password } = this.state;
        return (
            <form onSubmit={this.handleSubmit}>
                <div className="form-group">
                    <label htmlFor="email">Email</label>
                    <input
                        type="text"
                        className="form-control"
                        id="email"
                        value={email}
                        onChange={this.handleChange}
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="password">Password</label>
                    <input
                        type="password"
                        className="form-control"
                        id="password"
                        value={password}
                        onChange={this.handleChange}
                    />
                </div>
                <button type="submit" className="btn btn-success btn-lg">
                    LOGIN
                </button>
            </form>
        );
    }
}

const LoginForm = connect(null, mapDispatchToProps)(ConnectedLoginForm);

export default LoginForm;