import axios from 'axios';
import notify from 'devextreme/ui/notify';
import { isLoggedIn } from './authHelper';
export default {
    async logIn(email: string, password: string) {
        try {
            // Send request
            const user = await axios
                .post('api/login', {
                    email,
                    password,
                })
                .then((response) => response.data);
            return {
                isOk: true,
            };
        } catch {
            return {
                isOk: false,
                message: 'Ungültige Zugangsdaten',
            };
        }
    },

    async logOut() {
        await axios
            .post('api/logout')
            .then((response) => {
                notify('Du wurdest ausgeloggt', 'success');
            })
            .catch((error) => {
                notify('Fehler beim ausloggen', 'error');
            });
    },

    async resetPassword(email: string) {
        return axios
            .post('api/forgot-password', {
                email,
            })
            .then(() => {
                return {
                    isOk: true,
                };
            })
            .catch((error) => {
                let message = '';
                if (error.response && error.response.status === 422) {
                    for (const prop in error.response.data.errors) {
                        if (prop === 'email') {
                            const validationErrors = error.response.data.errors[prop] as Array<String>;
                            message = validationErrors.join(',');
                        }
                    }
                } else {
                    message = error;
                }
                return {
                    isOk: false,
                    message,
                };
            });
    },
    async changePassword(email: string, password: string, password_confirmation: string, token: string) {
        return axios
            .post('api/reset-password', {
                email,
                password,
                password_confirmation,
                token,
            })
            .then(() => {
                return {
                    isOk: true,
                };
            })
            .catch((error) => {
                let message = '';
                if (error.response && error.response.status === 422) {
                    for (const prop in error.response.data.errors) {
                        const validationErrors = error.response.data.errors[prop] as Array<String>;
                        message += validationErrors.join(',');
                    }
                } else {
                    message = error;
                }
                return {
                    isOk: false,
                    message,
                };
            });
    },

    async createAccount(email: string, password: string) {
        try {
            // Send request
            console.log(email, password);

            return {
                isOk: true,
            };
        } catch {
            return {
                isOk: false,
                message: 'Failed to create account',
            };
        }
    },
    loggedIn() {
        return isLoggedIn.value;
    },
};
