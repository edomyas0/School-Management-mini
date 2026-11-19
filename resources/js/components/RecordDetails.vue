<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, required: true },
    record: { type: Object, default: null },
    fields: { type: Array, required: true },
    loading: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div v-if="modelValue" class="dialog-backdrop" @click.self="emit('update:modelValue', false)">
        <section class="dialog-card details-card" role="dialog" aria-modal="true" :aria-label="title">
            <div class="dialog-header"><div><span class="eyebrow">RECORD PROFILE</span><h2>{{ title }}</h2></div><button class="icon-button" aria-label="Close dialog" @click="emit('update:modelValue', false)">×</button></div>
            <div v-if="loading" class="table-loading">Loading profile…</div>
            <dl v-else-if="record" class="detail-grid">
                <div v-for="field in fields" :key="field.label"><dt>{{ field.label }}</dt><dd>{{ field.value(record) || '—' }}</dd></div>
            </dl>
            <div class="dialog-footer"><button class="button button-secondary" @click="emit('update:modelValue', false)">Close</button></div>
        </section>
    </div>
</template>
