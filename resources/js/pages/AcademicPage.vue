<script setup>
import { computed, onMounted, ref } from 'vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import DataTable from '../components/DataTable.vue';
import PageHeading from '../components/PageHeading.vue';
import ResourceDialog from '../components/ResourceDialog.vue';
import { api, errorMessage } from '../services/api';
import { useToast } from '../stores/toast';

const toast = useToast();
const activeTab = ref('years');
const resources = ref({ years: [], grades: [], classes: [], subjects: [], assignments: [], teachers: [] });
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const pageError = ref('');
const modalOpen = ref(false);
const confirmOpen = ref(false);
const selected = ref(null);

const tabs = [
    { key: 'years', label: 'Academic years' },
    { key: 'grades', label: 'Grades' },
    { key: 'classes', label: 'Classes' },
    { key: 'subjects', label: 'Subjects' },
    { key: 'assignments', label: 'Teaching assignments' },
];

const definitions = {
    years: {
        title: 'academic year',
        endpoint: '/academic-years',
        columns: [{ key: 'name', label: 'Academic year' }, { key: 'starts_on', label: 'Starts' }, { key: 'ends_on', label: 'Ends' }, { key: 'is_current', label: 'Current year' }],
        fields: [
            { name: 'name', label: 'Academic year name', required: true, maxlength: 100 },
            { name: 'starts_on', label: 'Start date', type: 'date', required: true },
            { name: 'ends_on', label: 'End date', type: 'date', required: true },
            { name: 'is_current', label: 'Current academic year', type: 'select', required: true, default: 'false', options: [{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }] },
        ],
    },
    grades: {
        title: 'grade',
        endpoint: '/grades',
        columns: [{ key: 'name', label: 'Grade' }, { key: 'level', label: 'Level' }],
        fields: [{ name: 'name', label: 'Grade name', required: true, maxlength: 100 }, { name: 'level', label: 'Level number', type: 'number', required: true, min: 1, max: 100 }],
    },
    classes: {
        title: 'class',
        endpoint: '/classes',
        columns: [{ key: 'name', label: 'Class' }, { key: 'grade', label: 'Grade' }, { key: 'academic_year', label: 'Academic year' }, { key: 'class_teacher', label: 'Class teacher' }, { key: 'students_count', label: 'Students' }],
        fields: [
            { name: 'name', label: 'Class name', required: true, maxlength: 100 },
            { name: 'section', label: 'Section', required: true, default: 'A', maxlength: 50 },
            { name: 'academic_year_id', label: 'Academic year', type: 'select', required: true, placeholder: 'Choose a year', options: [] },
            { name: 'grade_id', label: 'Grade', type: 'select', required: true, placeholder: 'Choose a grade', options: [] },
            { name: 'class_teacher_id', label: 'Class teacher', type: 'select', placeholder: 'Not assigned', options: [] },
            { name: 'capacity', label: 'Capacity', type: 'number', required: true, default: 30, min: 1, max: 500 },
        ],
    },
    subjects: {
        title: 'subject',
        endpoint: '/subjects',
        columns: [{ key: 'code', label: 'Subject code' }, { key: 'name', label: 'Subject' }, { key: 'description', label: 'Description' }],
        fields: [{ name: 'code', label: 'Subject code', required: true, maxlength: 30 }, { name: 'name', label: 'Subject name', required: true, maxlength: 100 }, { name: 'description', label: 'Description', type: 'textarea', maxlength: 1000, wide: true }],
    },
    assignments: {
        title: 'teaching assignment',
        endpoint: '/teacher-assignments',
        columns: [{ key: 'teacher', label: 'Teacher' }, { key: 'subject', label: 'Subject' }, { key: 'school_class', label: 'Class' }, { key: 'academic_year', label: 'Academic year' }],
        fields: [
            { name: 'teacher_id', label: 'Teacher', type: 'select', required: true, placeholder: 'Choose a teacher', options: [] },
            { name: 'subject_id', label: 'Subject', type: 'select', required: true, placeholder: 'Choose a subject', options: [] },
            { name: 'school_class_id', label: 'Class', type: 'select', required: true, placeholder: 'Choose a class', options: [] },
            { name: 'academic_year_id', label: 'Academic year', type: 'select', required: true, placeholder: 'Choose a year', options: [] },
        ],
    },
};

const current = computed(() => definitions[activeTab.value]);
const formFields = computed(() => {
    if (activeTab.value === 'classes') {
        return current.value.fields.map((field) => {
            if (field.name === 'academic_year_id') return { ...field, options: resources.value.years.map((item) => ({ value: String(item.id), label: item.name })) };
            if (field.name === 'grade_id') return { ...field, options: resources.value.grades.map((item) => ({ value: String(item.id), label: item.name })) };
            if (field.name === 'class_teacher_id') return { ...field, options: resources.value.teachers.map((item) => ({ value: String(item.id), label: item.full_name })) };
            return field;
        });
    }
    if (activeTab.value === 'assignments') {
        return current.value.fields.map((field) => {
            if (field.name === 'teacher_id') return { ...field, options: resources.value.teachers.map((item) => ({ value: String(item.id), label: `${item.full_name} · ${item.staff_number}` })) };
            if (field.name === 'subject_id') return { ...field, options: resources.value.subjects.map((item) => ({ value: String(item.id), label: `${item.name} · ${item.code}` })) };
            if (field.name === 'school_class_id') return { ...field, options: resources.value.classes.map((item) => ({ value: String(item.id), label: `${item.grade} · ${item.section} · ${item.academic_year}` })) };
            if (field.name === 'academic_year_id') return { ...field, options: resources.value.years.map((item) => ({ value: String(item.id), label: item.name })) };
            return field;
        });
    }
    return current.value.fields;
});

async function load() {
    loading.value = true;
    pageError.value = '';
    try {
        const [years, grades, classes, subjects, teachers, assignments] = await Promise.all([
            api.get('/academic-years', { params: { per_page: 100 } }),
            api.get('/grades', { params: { per_page: 100 } }),
            api.get('/classes', { params: { per_page: 100 } }),
            api.get('/subjects', { params: { per_page: 100 } }),
            api.get('/teachers', { params: { per_page: 100 } }),
            api.get('/teacher-assignments', { params: { per_page: 100 } }),
        ]);
        resources.value = {
            years: years.data.data,
            grades: grades.data.data,
            classes: classes.data.data,
            subjects: subjects.data.data,
            teachers: teachers.data.data,
            assignments: assignments.data.data,
        };
    } catch (error) {
        pageError.value = errorMessage(error);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function save(values) {
    saving.value = true;
    const data = activeTab.value === 'years' ? { ...values, is_current: values.is_current === 'true' } : values;
    try {
        if (activeTab.value === 'assignments') {
            await api.post(current.value.endpoint, data);
            toast.show('Teaching assignment saved.');
        } else if (selected.value) {
            await api.put(`${current.value.endpoint}/${selected.value.id}`, data);
            toast.show(`${current.value.title} updated.`);
        } else {
            await api.post(current.value.endpoint, data);
            toast.show(`${current.value.title} created.`);
        }
        modalOpen.value = false;
        selected.value = null;
        await load();
    } catch (error) {
        const errors = error.response?.data?.errors || { message: errorMessage(error) };
        modalErrors.value = errors;
    } finally {
        saving.value = false;
    }
}

const modalErrors = ref({});

function openDialog() {
    modalErrors.value = {};
    if (selected.value && activeTab.value === 'years') selected.value = { ...selected.value, is_current: String(selected.value.is_current) };
    modalOpen.value = true;
}

function chooseEdit(row) {
    selected.value = row;
    openDialog();
}

async function remove() {
    deleting.value = true;
    try {
        await api.delete(`${current.value.endpoint}/${selected.value.id}`);
        confirmOpen.value = false;
        toast.show(`${current.value.title} removed.`);
        selected.value = null;
        await load();
    } catch (error) {
        toast.show(errorMessage(error), 'error');
    } finally {
        deleting.value = false;
    }
}

function beginRemove(row) {
    selected.value = row;
    confirmOpen.value = true;
}

function displayAssignment(row, key) {
    if (key === 'teacher') return `${row.teacher?.first_name || ''} ${row.teacher?.last_name || ''}`.trim();
    if (key === 'subject') return row.subject?.name || '—';
    if (key === 'school_class') return row.school_class ? `${row.school_class.name} · ${row.school_class.section}` : '—';
    if (key === 'academic_year') return row.academic_year?.name || '—';
    return row[key];
}
</script>

<template>
    <PageHeading title="Academic setup" description="Set academic years, grade levels, classes, subjects, and teaching assignments.">
        <button class="button button-primary" @click="selected = null; openDialog()">{{ activeTab === 'assignments' ? 'Assign teacher' : `Add ${current.title}` }}</button>
    </PageHeading>
    <div class="tabs" role="tablist" aria-label="Academic setup">
        <button v-for="tab in tabs" :key="tab.key" class="tab-button" :class="{ 'tab-active': activeTab === tab.key }" role="tab" :aria-selected="activeTab === tab.key" @click="activeTab = tab.key; selected = null; modalErrors = {}">{{ tab.label }}</button>
    </div>
    <p v-if="pageError" class="form-alert page-alert" role="alert">{{ pageError }} <button class="text-button" @click="load">Try again</button></p>
    <DataTable :columns="current.columns" :rows="resources[activeTab]" :loading="loading" :empty-title="`No ${current.title} records`" :empty-description="`Add a ${current.title} to set up your school.`">
        <template #cell-is_current="{ value }"><span class="status-chip" :class="value ? 'status-active' : 'status-neutral'">{{ value ? 'Current' : 'Upcoming / past' }}</span></template>
        <template v-for="column in activeTab === 'assignments' ? current.columns : []" :key="column.key" #[`cell-${column.key}`]="{ row }">{{ displayAssignment(row, column.key) }}</template>
        <template #actions="{ row }">
            <button v-if="activeTab !== 'assignments'" class="text-button" @click="chooseEdit(row)">Edit</button>
            <button class="text-button text-danger" @click="beginRemove(row)">Remove</button>
        </template>
    </DataTable>
    <ResourceDialog v-model="modalOpen" :title="selected ? `Edit ${current.title}` : `Add ${current.title}`" :fields="formFields" :initial-data="selected || {}" :errors="modalErrors" :loading="saving" :submit-label="selected ? 'Save changes' : 'Create record'" @submit="save" />
    <ConfirmDialog v-model="confirmOpen" :title="`Remove this ${current.title}?`" description="Records referenced by student enrollments or teaching assignments cannot be removed." :loading="deleting" @confirm="remove" />
</template>
