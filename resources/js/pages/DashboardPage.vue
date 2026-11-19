<script setup>
import { onMounted, ref } from 'vue';
import PageHeading from '../components/PageHeading.vue';
import { api, errorMessage } from '../services/api';
import { useToast } from '../stores/toast';

const toast = useToast();
const loading = ref(true);
const dashboard = ref(null);

async function load() {
    loading.value = true;
    try {
        const response = await api.get('/dashboard');
        dashboard.value = response.data;
    } catch (error) {
        toast.show(errorMessage(error), 'error');
    } finally {
        loading.value = false;
    }
}

function formatDate(value) {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);
    return new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(year, month - 1, day));
}

function formatRelative(value) {
    const minutes = Math.round((Date.now() - new Date(value).getTime()) / 60000);
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes} min ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} hr ago`;
    return formatDate(value);
}

onMounted(load);
</script>

<template>
    <PageHeading title="Dashboard" description="A current view of your school community and academic year.">
        <button class="button button-secondary" :disabled="loading" @click="load">{{ loading ? 'Refreshing…' : 'Refresh data' }}</button>
    </PageHeading>

    <div v-if="loading && !dashboard" class="dashboard-loading" role="status">Loading school overview…</div>
    <template v-else-if="dashboard">
        <section class="stat-grid" aria-label="School statistics">
            <article v-for="stat in [
                { label: 'Total students', key: 'students', note: 'Student records' },
                { label: 'Total teachers', key: 'teachers', note: 'Teaching staff' },
                { label: 'Total employees', key: 'employees', note: 'Staff and school accounts' },
                { label: 'Total classes', key: 'classes', note: 'School class groups' },
            ]" :key="stat.key" class="stat-card">
                <span>{{ stat.label }}</span><strong>{{ dashboard.statistics[stat.key].toLocaleString() }}</strong><small>{{ stat.note }}</small>
            </article>
        </section>

        <section class="dashboard-secondary">
            <article class="panel-card attendance-card">
                <div class="panel-heading"><div><h2>Attendance overview</h2><p>Daily attendance reporting</p></div><span class="status-chip status-neutral">{{ dashboard.attendance.available ? 'Active' : 'Not configured' }}</span></div>
                <div v-if="dashboard.attendance.available" class="panel-empty">Attendance records are available.</div>
                <div v-else class="feature-notice"><span class="notice-mark">—</span><div><strong>Attendance tracking is not available yet</strong><p>Daily registers and attendance reporting are planned for a later release.</p></div></div>
            </article>
            <article class="panel-card year-card">
                <div class="panel-heading"><div><h2>Academic year</h2><p>Current school calendar</p></div></div>
                <div v-if="dashboard.academic_year" class="year-summary"><strong>{{ dashboard.academic_year.name }}</strong><span>{{ formatDate(dashboard.academic_year.starts_on) }} — {{ formatDate(dashboard.academic_year.ends_on) }}</span></div>
                <div v-else class="panel-empty">No current academic year has been set up.</div>
                <div v-if="dashboard.upcoming_events.length" class="upcoming-list">
                    <span class="eyebrow">UPCOMING YEARS</span>
                    <div v-for="event in dashboard.upcoming_events" :key="event.id" class="upcoming-row"><strong>{{ event.name }}</strong><span>{{ formatDate(event.starts_on) }}</span></div>
                </div>
            </article>
        </section>

        <section class="panel-card activity-card">
            <div class="panel-heading"><div><h2>Recent activity</h2><p>Latest student and staff record updates</p></div></div>
            <div v-if="dashboard.recent_activities.length" class="activity-list">
                <div v-for="activity in dashboard.recent_activities" :key="activity.id" class="activity-row">
                    <span class="activity-dot"></span><span class="activity-description">{{ activity.description }}</span><time>{{ formatRelative(activity.occurred_at) }}</time>
                </div>
            </div>
            <div v-else class="panel-empty">New student and staff activity will appear here.</div>
        </section>
    </template>
    <div v-else class="panel-card panel-empty">The school overview could not be loaded. Use “Refresh data” to try again.</div>
</template>
