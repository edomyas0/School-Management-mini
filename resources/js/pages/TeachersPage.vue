<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import DataTable from '../components/DataTable.vue';
import PageHeading from '../components/PageHeading.vue';
import RecordDetails from '../components/RecordDetails.vue';
import ResourceDialog from '../components/ResourceDialog.vue';
import { api, errorMessage } from '../services/api';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../stores/toast';

const toast = useToast();
const auth = useAuthStore();
const rows = ref([]);
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const pageError = ref('');
const search = ref('');
const status = ref('');
const page = ref(1);
const lastPage = ref(1);
const modalOpen = ref(false);
const confirmOpen = ref(false);
const detailsOpen = ref(false);
const selected = ref(null);
const detailsLoading = ref(false);
const formErrors = ref({});
const timer = ref(null);
const fields = [
    { name: 'staff_number', label: 'Staff number', required: true, maxlength: 50 },
    { name: 'first_name', label: 'First name', required: true, maxlength: 100 },
    { name: 'last_name', label: 'Last name', required: true, maxlength: 100 },
    { name: 'email', label: 'Email address', type: 'email', required: true, maxlength: 255 },
    { name: 'phone', label: 'Phone number', maxlength: 32 },
    { name: 'employment_type', label: 'Employment type', type: 'select', required: true, default: 'full_time', options: [{ value: 'full_time', label: 'Full time' }, { value: 'part_time', label: 'Part time' }, { value: 'contract', label: 'Contract' }] },
    { name: 'hired_on', label: 'Employment start date', type: 'date', required: true },
    { name: 'status', label: 'Status', type: 'select', required: true, default: 'active', options: [{ value: 'active', label: 'Active' }, { value: 'on_leave', label: 'On leave' }, { value: 'inactive', label: 'Inactive' }] },
    { name: 'qualification', label: 'Qualification', maxlength: 150 },
    { name: 'institution', label: 'Institution', maxlength: 150 },
    { name: 'awarded_year', label: 'Awarded year', type: 'number', min: 1950, max: new Date().getFullYear() },
];
const columns = [
    { key: 'staff_number', label: 'Staff no.' },
    { key: 'full_name', label: 'Teacher' },
    { key: 'qualification', label: 'Qualification' },
    { key: 'employment_type', label: 'Employment' },
    { key: 'status', label: 'Status' },
];
const detailFields = [
    { label: 'Staff number', value: (record) => record.staff_number },
    { label: 'Email address', value: (record) => record.email },
    { label: 'Phone number', value: (record) => record.phone },
    { label: 'Employment type', value: (record) => record.employment_type?.replaceAll('_', ' ') },
    { label: 'Start date', value: (record) => record.hired_on },
    { label: 'Qualifications', value: (record) => record.qualifications?.map((item) => `${item.title}${item.institution ? ` · ${item.institution}` : ''}`).join('; ') },
    { label: 'Assigned subjects and classes', value: (record) => record.assignments?.map((item) => `${item.subject} · ${item.class} (${item.academic_year})`).join(', ') },
    { label: 'Status', value: (record) => record.status?.replaceAll('_', ' ') },
];

async function load() {
    loading.value = true;
    pageError.value = '';
    try {
        const response = await api.get('/teachers', { params: { search: search.value, status: status.value || undefined, page: page.value, per_page: 15 } });
        rows.value = response.data.data.map((teacher) => ({
            ...teacher,
            qualification: teacher.qualifications?.find((item) => item.is_primary)?.title || teacher.qualifications?.[0]?.title || '—',
        }));
        page.value = response.data.meta.current_page;
        lastPage.value = response.data.meta.last_page;
    } catch (error) {
        pageError.value = errorMessage(error);
    } finally {
        loading.value = false;
    }
}

watch([search, status], () => {
    clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        page.value = 1;
        load();
    }, 300);
});
onMounted(load);
onBeforeUnmount(() => clearTimeout(timer.value));

function beginCreate() {
    selected.value = null;
    formErrors.value = {};
    modalOpen.value = true;
}

async function beginEdit(row) {
    formErrors.value = {};
    try {
        const response = await api.get(`/teachers/${row.id}`);
        selected.value = response.data.data;
        const qualification = selected.value.qualifications?.find((item) => item.is_primary) || selected.value.qualifications?.[0];
        selected.value = { ...selected.value, qualification: qualification?.title || '', institution: qualification?.institution || '', awarded_year: qualification?.awarded_year || '' };
        modalOpen.value = true;
    } catch (error) {
        toast.show(errorMessage(error), 'error');
    }
}

async function showDetails(row) {
    selected.value = null;
    detailsLoading.value = true;
    detailsOpen.value = true;
    try {
        const response = await api.get(`/teachers/${row.id}`);
        selected.value = response.data.data;
    } catch (error) {
        detailsOpen.value = false;
        toast.show(errorMessage(error), 'error');
    } finally {
        detailsLoading.value = false;
    }
}

async function save(values) {
    saving.value = true;
    formErrors.value = {};
    try {
        if (selected.value) {
            await api.put(`/teachers/${selected.value.id}`, values);
            toast.show('Teacher record updated.');
        } else {
            await api.post('/teachers', values);
            toast.show('Teacher added.');
        }
        modalOpen.value = false;
        await load();
    } catch (error) {
        formErrors.value = error.response?.data?.errors || { message: errorMessage(error) };
    } finally {
        saving.value = false;
    }
}

async function remove() {
    deleting.value = true;
    try {
        await api.delete(`/teachers/${selected.value.id}`);
        confirmOpen.value = false;
        toast.show('Teacher record archived.');
        await load();
    } catch (error) {
        toast.show(errorMessage(error), 'error');
    } finally {
        deleting.value = false;
    }
}

function confirmRemove(row) {
    selected.value = row;
    confirmOpen.value = true;
}
</script>

<template>
    <PageHeading title="Teachers" description="Maintain staff profiles, qualifications, employment details, and teaching assignments.">
        <button v-if="auth.user?.role === 'registrar'" class="button button-primary" @click="beginCreate">Add teacher</button>
    </PageHeading>
    <div class="toolbar">
        <label class="search-field"><span aria-hidden="true">⌕</span><input v-model="search" type="search" placeholder="Search name, staff no. or email" aria-label="Search teachers" /></label>
        <select v-model="status" class="filter-select" aria-label="Filter by status"><option value="">All statuses</option><option value="active">Active</option><option value="on_leave">On leave</option><option value="inactive">Inactive</option></select>
        <span class="result-count">{{ loading ? 'Updating…' : `${rows.length} shown` }}</span>
    </div>
    <p v-if="pageError" class="form-alert page-alert" role="alert">{{ pageError }} <button class="text-button" @click="load">Try again</button></p>
    <DataTable :columns="columns" :rows="rows" :loading="loading" :page="page" :last-page="lastPage" empty-title="No teachers found" empty-description="Add a teacher or change your search criteria." @page="page = $event; load()">
        <template #cell-employment_type="{ value }">{{ value?.replaceAll('_', ' ') }}</template>
        <template #cell-status="{ value }"><span class="status-chip" :class="`status-${value}`">{{ value?.replaceAll('_', ' ') }}</span></template>
        <template #actions="{ row }">
            <button class="text-button" @click="showDetails(row)">View</button>
            <button class="text-button" @click="beginEdit(row)">Edit</button>
            <button v-if="auth.user?.role === 'administrator'" class="text-button text-danger" @click="confirmRemove(row)">Archive</button>
        </template>
    </DataTable>
    <ResourceDialog v-model="modalOpen" :title="selected ? 'Edit teacher record' : 'Add a teacher'" :fields="fields" :initial-data="selected || {}" :errors="formErrors" :loading="saving" :submit-label="selected ? 'Save changes' : 'Add teacher'" @submit="save" />
    <ConfirmDialog v-model="confirmOpen" title="Archive this teacher?" description="The teacher profile will be hidden from active lists. Existing class assignments will be retained." :loading="deleting" @confirm="remove" />
    <RecordDetails v-model="detailsOpen" :title="selected?.full_name || 'Teacher profile'" :record="selected" :fields="detailFields" :loading="detailsLoading" />
</template>
