<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
const classes = ref([]);
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
const fields = computed(() => [
    { name: 'admission_number', label: 'Admission number', required: true, maxlength: 50 },
    { name: 'first_name', label: 'First name', required: true, maxlength: 100 },
    { name: 'last_name', label: 'Last name', required: true, maxlength: 100 },
    { name: 'date_of_birth', label: 'Date of birth', type: 'date' },
    { name: 'gender', label: 'Gender', type: 'select', placeholder: 'Not specified', options: [{ value: 'female', label: 'Female' }, { value: 'male', label: 'Male' }, { value: 'other', label: 'Other' }, { value: 'prefer_not_to_say', label: 'Prefer not to say' }] },
    { name: 'email', label: 'Email address', type: 'email', maxlength: 255 },
    { name: 'phone', label: 'Phone number', maxlength: 32 },
    { name: 'status', label: 'Status', type: 'select', required: true, default: 'active', options: [{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }, { value: 'graduated', label: 'Graduated' }, { value: 'transferred', label: 'Transferred' }] },
    { name: 'class_id', label: 'Class', type: 'select', placeholder: 'Not assigned', options: classes.value.map((item) => ({ value: String(item.id), label: `${item.name} · ${item.section} · ${item.academic_year}` })) },
]);

const columns = [
    { key: 'admission_number', label: 'Admission no.' },
    { key: 'full_name', label: 'Student' },
    { key: 'class', label: 'Class' },
    { key: 'status', label: 'Status' },
    { key: 'email', label: 'Email' },
];
const detailFields = [
    { label: 'Admission number', value: (record) => record.admission_number },
    { label: 'Date of birth', value: (record) => record.date_of_birth },
    { label: 'Gender', value: (record) => record.gender?.replaceAll('_', ' ') },
    { label: 'Email address', value: (record) => record.email },
    { label: 'Phone number', value: (record) => record.phone },
    { label: 'Class', value: (record) => record.class ? `${record.class.grade} · ${record.class.section} (${record.class.academic_year})` : null },
    { label: 'Status', value: (record) => record.status },
];

async function load() {
    loading.value = true;
    pageError.value = '';
    try {
        const [studentResponse, classResponse] = await Promise.all([
            api.get('/students', { params: { search: search.value, status: status.value || undefined, page: page.value, per_page: 15 } }),
            api.get('/classes', { params: { per_page: 100 } }),
        ]);
        rows.value = studentResponse.data.data;
        page.value = studentResponse.data.meta.current_page;
        lastPage.value = studentResponse.data.meta.last_page;
        classes.value = classResponse.data.data;
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
        const response = await api.get(`/students/${row.id}`);
        selected.value = response.data.data;
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
        const response = await api.get(`/students/${row.id}`);
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
    const data = { ...values, class_id: values.class_id || null };
    try {
        if (selected.value) {
            await api.put(`/students/${selected.value.id}`, data);
            toast.show('Student record updated.');
        } else {
            await api.post('/students', data);
            toast.show('Student registered.');
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
        await api.delete(`/students/${selected.value.id}`);
        confirmOpen.value = false;
        toast.show('Student record archived.');
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

function formatClass(value) {
    return value ? `${value.grade} · ${value.section}` : 'Unassigned';
}
</script>

<template>
    <PageHeading title="Students" description="Register students, manage class placements, and keep student records current.">
        <button class="button button-primary" @click="beginCreate">Register student</button>
    </PageHeading>
    <div class="toolbar">
        <label class="search-field"><span aria-hidden="true">⌕</span><input v-model="search" type="search" placeholder="Search name, admission no. or email" aria-label="Search students" /></label>
        <select v-model="status" class="filter-select" aria-label="Filter by status"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="graduated">Graduated</option><option value="transferred">Transferred</option></select>
        <span class="result-count">{{ loading ? 'Updating…' : `${rows.length} shown` }}</span>
    </div>
    <p v-if="pageError" class="form-alert page-alert" role="alert">{{ pageError }} <button class="text-button" @click="load">Try again</button></p>
    <DataTable :columns="columns" :rows="rows" :loading="loading" :page="page" :last-page="lastPage" empty-title="No students found" empty-description="Register a student or change your search criteria." @page="page = $event; load()">
        <template #cell-class="{ value }">{{ formatClass(value) }}</template>
        <template #cell-status="{ value }"><span class="status-chip" :class="`status-${value}`">{{ value }}</span></template>
        <template #actions="{ row }">
            <button class="text-button" @click="showDetails(row)">View</button>
            <button class="text-button" @click="beginEdit(row)">Edit</button>
            <button v-if="auth.user?.role === 'administrator'" class="text-button text-danger" @click="confirmRemove(row)">Archive</button>
        </template>
    </DataTable>
    <ResourceDialog v-model="modalOpen" :title="selected ? 'Edit student record' : 'Register a student'" :fields="fields" :initial-data="selected ? { ...selected, class_id: selected.class ? String(selected.class.id) : '' } : {}" :errors="formErrors" :loading="saving" :submit-label="selected ? 'Save changes' : 'Register student'" @submit="save" />
    <ConfirmDialog v-model="confirmOpen" title="Archive this student?" description="The student record will be hidden from active lists. Existing enrollment history will be retained." :loading="deleting" @confirm="remove" />
    <RecordDetails v-model="detailsOpen" :title="selected?.full_name || 'Student profile'" :record="selected" :fields="detailFields" :loading="detailsLoading" />
</template>
