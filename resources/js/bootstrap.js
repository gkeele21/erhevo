import axios from 'axios';
import { router } from '@inertiajs/vue3';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Once here, stop: the browser is already on its way to the login page, so no
// caller should get a chance to render its own "something went wrong".
const goToLogin = (url) => {
    window.location.href = url || '/login';

    return new Promise(() => {});
};

// A tab left open past the session lifetime fails its next XHR with a 401 (the
// server's answer for an expired session; see bootstrap/app.php) or a 419 if it
// beat the exception handler. Either way the user is logged out — send them to
// the login page rather than surfacing an inline error they can't act on.
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401 || status === 419) {
            return goToLogin(error.response?.data?.redirect);
        }

        return Promise.reject(error);
    },
);

// Safety net for Inertia visits: "invalid" fires when a response comes back
// that Inertia can't render — an expired session hitting a route that answers
// with HTML instead of redirecting. Swallow the error modal and log in again.
router.on('invalid', (event) => {
    const status = event.detail.response?.status;

    if (status === 401 || status === 419) {
        event.preventDefault();
        goToLogin();
    }
});
