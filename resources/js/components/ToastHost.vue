<script setup>
import { useToast } from '../stores/toast';

const { message, kind, dismiss } = useToast();
</script>

<template>
    <div v-if="message" class="toast" :class="`toast-${kind}`" role="status">
        <span>{{ message }}</span><button aria-label="Dismiss notification" @click="dismiss">×</button>
    </div>
</template>
