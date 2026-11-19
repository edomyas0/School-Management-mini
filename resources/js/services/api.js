import axios from 'axios';

export const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export async function csrf() {
    await axios.get('/sanctum/csrf-cookie', {
        withCredentials: true,
        withXSRFToken: true,
    });
}

export function errorMessage(error) {
    return error.response?.data?.message || 'Something went wrong. Please try again.';
}
