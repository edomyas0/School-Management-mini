import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AppShell from '../layouts/AppShell.vue';
import LoginPage from '../pages/LoginPage.vue';
import DashboardPage from '../pages/DashboardPage.vue';
import StudentsPage from '../pages/StudentsPage.vue';
import TeachersPage from '../pages/TeachersPage.vue';
import AcademicPage from '../pages/AcademicPage.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: LoginPage, meta: { guest: true } },
        {
            path: '/',
            component: AppShell,
            meta: { requiresAuth: true },
            children: [
                { path: '', redirect: { name: 'dashboard' } },
                { path: 'dashboard', name: 'dashboard', component: DashboardPage, meta: { label: 'Dashboard' } },
                { path: 'students', name: 'students', component: StudentsPage, meta: { label: 'Students', roles: ['administrator', 'registrar'] } },
                { path: 'teachers', name: 'teachers', component: TeachersPage, meta: { label: 'Teachers', roles: ['administrator', 'registrar'] } },
                { path: 'academic', name: 'academic', component: AcademicPage, meta: { label: 'Academic setup', roles: ['administrator', 'registrar'] } },
            ],
        },
        { path: '/:pathMatch(.*)*', redirect: { name: 'dashboard' } },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (auth.user === null && !auth.loading) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { path: '/login', query: { redirect: to.fullPath } };
    }
    if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
        return { name: 'dashboard' };
    }
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
