<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../stores/toast';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        await auth.login({ email: email.value, password: password.value });
        await router.push(typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard');
    } catch (requestError) {
        error.value = requestError.response?.data?.errors?.email?.[0]
            || requestError.response?.data?.message
            || 'Unable to sign in. Please check your connection and try again.';
        toast.show('Sign in failed. Check your email and password.', 'error');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="login-layout">
        <section class="login-brand-panel">
            <div class="brand brand-light"><span class="brand-mark">N</span><span><strong>Northstar</strong><small>School administration</small></span></div>
            <div class="login-brand-copy"><span class="eyebrow">SCHOOL ADMINISTRATION</span><h1>One clear view of your school.</h1><p>Manage your school community, records, and academic year in one place.</p></div>
            <span class="login-brand-foot">A considered workspace for school teams.</span>
        </section>
        <section class="login-form-panel">
            <form class="login-form" @submit.prevent="submit">
                <span class="eyebrow">WELCOME BACK</span>
                <h2>Sign in to your account</h2>
                <p class="login-copy">Use your school account to continue.</p>
                <label class="form-field"><span>Email address</span><input v-model="email" type="email" autocomplete="username" required autofocus /></label>
                <label class="form-field"><span>Password</span><input v-model="password" type="password" autocomplete="current-password" required /></label>
                <p v-if="error" class="form-alert" role="alert">{{ error }}</p>
                <button class="button button-primary login-submit" type="submit" :disabled="loading">{{ loading ? 'Signing in…' : 'Sign in' }}</button>
            </form>
        </section>
    </main>
</template>
