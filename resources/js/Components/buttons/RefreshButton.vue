<script setup>
defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['refresh']);
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)] disabled:opacity-60"
        :disabled="loading"
        @click="$emit('refresh')"
    >
        <font-awesome-icon
            :icon="['fas', 'refresh']"
            :class="loading ? 'animate-spin' : ''"
        />
        <span>{{ loading ? 'Actualizando...' : 'Refrescar' }}</span>
    </button>
</template>
