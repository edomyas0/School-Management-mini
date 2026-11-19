<script setup>
import { computed, ref } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../stores/toast';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const toast = useToast();
const menuOpen = ref(false);
const pageTitle = computed(() => route.meta.label || 'School administration');

async function signOut() {
    try {
        await auth.logout();
        await router.push('/login');
    } catch {
        toast.show('Unable to sign out. Please try again.', 'error');
    }
}
</script>

<template>
    <div class="app-frame">
        <button v-if="menuOpen" class="sidebar-scrim" aria-label="Close navigation" @click="menuOpen = false"></button>
        <aside class="sidebar" :class="{ 'sidebar-open': menuOpen }">
            <RouterLink class="brand" to="/dashboard" @click="menuOpen = false">
                <span class="brand-mark">N</span>
                <span><strong>Northstar</strong><small>School administration</small></span>
            </RouterLink>
            <div class="nav-caption">WORKSPACE</div>
            <nav class="navigation" aria-label="Main navigation">
                <RouterLink class="nav-link" to="/dashboard" @click="menuOpen = false">
                    <span class="nav-glyph">01</span>Dashboard
                </RouterLink>
                <template v-if="['administrator', 'registrar'].includes(auth.user?.role)">
                    <div class="nav-caption nav-section">PEOPLE</div>
                    <RouterLink class="nav-link" to="/students" @click="menuOpen = false">
                        <span class="nav-glyph">02</span>Students
                    </RouterLink>
                    <RouterLink class="nav-link" to="/teachers" @click="menuOpen = false">
                        <span class="nav-glyph">03</span>Teachers
                    </RouterLink>
                    <div class="nav-caption nav-section">ACADEMICS</div>
                    <RouterLink class="nav-link" to="/academic" @click="menuOpen = false">
                        <span class="nav-glyph">04</span>Academic setup
                    </RouterLink>
                </template>
            </nav>
            <div class="sidebar-bottom">
                <span class="status-dot"></span>
                <span>School workspace</span>
                <span class="sidebar-version">v1</span>
            </div>
        </aside>

        <div class="workspace">
            <header class="topbar">
                <button class="mobile-menu" aria-label="Open navigation" @click="menuOpen = !menuOpen">☰</button>
                <div class="topbar-context">
                    <span class="topbar-school">Northstar School</span>
                    <span class="topbar-divider">/</span>
                    <span>{{ pageTitle }}</span>
                </div>
                <div class="topbar-account">
                    <div class="account-copy">
                        <strong>{{ auth.user?.name }}</strong>
                        <span>{{ auth.user?.role }}</span>
                    </div>
                    <button class="button button-quiet button-small" @click="signOut">Sign out</button>
                </div>
            </header>
            <main class="main-content">
                <div class="breadcrumbs"><RouterLink to="/dashboard">Workspace</RouterLink><span>/</span><strong>{{ pageTitle }}</strong></div>
                <RouterView />
                <footer class="page-footer">Northstar School · Administration</footer>
            </main>
        </div>
    </div>
</template>
