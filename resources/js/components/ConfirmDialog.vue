<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: 'Remove this record?' },
    description: { type: String, default: 'This action cannot be undone.' },
    loading: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'confirm']);
</script>

<template>
    <div v-if="modelValue" class="dialog-backdrop" @click.self="emit('update:modelValue', false)">
        <section class="dialog-card confirm-card" role="alertdialog" aria-modal="true" :aria-label="title">
            <h2>{{ title }}</h2><p>{{ description }}</p>
            <div class="dialog-footer"><button class="button button-secondary" :disabled="loading" @click="emit('update:modelValue', false)">Cancel</button><button class="button button-danger" :disabled="loading" @click="emit('confirm')">{{ loading ? 'Removing…' : 'Remove record' }}</button></div>
        </section>
    </div>
</template>
