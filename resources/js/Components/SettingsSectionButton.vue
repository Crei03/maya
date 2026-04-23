<script setup>
defineProps({
    icon: {
        type: Array,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)]"
        :class="active
            ? 'border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] shadow-sm'
            : 'border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] hover:border-[var(--maya-text-muted)]'"
        @click="$emit('click')"
    >
        <font-awesome-icon :icon="icon" class="text-base" />
        <span>{{ label }}</span>
    </button>
</template>
