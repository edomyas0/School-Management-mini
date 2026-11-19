import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { api, csrf } from '../services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const isAuthenticated = computed(() => user.value !== null);

    async function fetchUser() {
        loading.value = true;
        try {
            const response = await api.get('/user');
            user.value = response.data.data;
        } catch (error) {
            if (error.response?.status === 401) {
                user.value = null;
                return;
            }
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function login(credentials) {
        await csrf();
        const response = await api.post('/login', credentials);
        user.value = response.data.data;
        return user.value;
    }

    async function logout() {
        await api.post('/logout');
        user.value = null;
    }

    return { user, loading, isAuthenticated, fetchUser, login, logout };
});
