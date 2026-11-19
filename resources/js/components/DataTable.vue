<script setup>
defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    emptyTitle: { type: String, default: 'No records found' },
    emptyDescription: { type: String, default: 'Try adjusting your search or add a new record.' },
    page: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
});

defineEmits(['page']);
</script>

<template>
    <div class="table-card">
        <div v-if="loading && rows.length === 0" class="table-loading" role="status">Loading records…</div>
        <div v-else class="table-scroll">
            <table>
                <thead><tr><th v-for="column in columns" :key="column.key" :class="{ 'text-right': column.align === 'right' }">{{ column.label }}</th><th v-if="$slots.actions" class="text-right">Actions</th></tr></thead>
                <tbody v-if="rows.length">
                    <tr v-for="row in rows" :key="row.id">
                        <td v-for="column in columns" :key="column.key">
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">{{ row[column.key] || '—' }}</slot>
                        </td>
                        <td v-if="$slots.actions" class="table-actions"><slot name="actions" :row="row"></slot></td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!rows.length && !loading" class="empty-state"><strong>{{ emptyTitle }}</strong><span>{{ emptyDescription }}</span></div>
            <div v-if="loading && rows.length" class="table-refreshing" role="status">Updating…</div>
        </div>
        <div v-if="lastPage > 1" class="pagination">
            <span>Page {{ page }} of {{ lastPage }}</span>
            <div><button class="button button-quiet button-small" :disabled="page <= 1 || loading" @click="$emit('page', page - 1)">Previous</button><button class="button button-quiet button-small" :disabled="page >= lastPage || loading" @click="$emit('page', page + 1)">Next</button></div>
        </div>
    </div>
</template>
