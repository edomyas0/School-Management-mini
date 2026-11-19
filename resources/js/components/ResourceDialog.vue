<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, required: true },
    fields: { type: Array, required: true },
    initialData: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    submitLabel: { type: String, default: 'Save record' },
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue', 'submit']);
const form = reactive({});

watch(() => [props.modelValue, props.initialData], () => {
    if (!props.modelValue) return;
    for (const key of Object.keys(form)) delete form[key];
    for (const field of props.fields) {
        const value = props.initialData[field.name];
        form[field.name] = value ?? field.default ?? '';
    }
}, { immediate: true, deep: true });
</script>

<template>
    <div v-if="modelValue" class="dialog-backdrop" @click.self="emit('update:modelValue', false)">
        <section class="dialog-card" role="dialog" aria-modal="true" :aria-label="title">
            <div class="dialog-header"><h2>{{ title }}</h2><button class="icon-button" aria-label="Close dialog" @click="emit('update:modelValue', false)">×</button></div>
            <form class="dialog-body" @submit.prevent="emit('submit', { ...form })">
                <div class="form-grid">
                    <label v-for="field in fields" :key="field.name" class="form-field" :class="{ 'field-wide': field.wide }">
                        <span>{{ field.label }}<em v-if="field.required"> *</em></span>
                        <select v-if="field.type === 'select'" v-model="form[field.name]" :required="field.required" :disabled="loading">
                            <option v-if="field.placeholder" value="">{{ field.placeholder }}</option>
                            <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <textarea v-else-if="field.type === 'textarea'" v-model="form[field.name]" :required="field.required" :maxlength="field.maxlength" rows="3" :disabled="loading"></textarea>
                        <input v-else v-model="form[field.name]" :type="field.type || 'text'" :required="field.required" :maxlength="field.maxlength" :min="field.min" :max="field.max" :disabled="loading" />
                        <small v-if="errors[field.name]?.[0]" class="field-error">{{ errors[field.name][0] }}</small>
                    </label>
                </div>
                <p v-if="errors.message" class="form-alert">{{ errors.message }}</p>
                <div class="dialog-footer"><button type="button" class="button button-secondary" :disabled="loading" @click="emit('update:modelValue', false)">Cancel</button><button type="submit" class="button button-primary" :disabled="loading">{{ loading ? 'Saving…' : submitLabel }}</button></div>
            </form>
        </section>
    </div>
</template>
