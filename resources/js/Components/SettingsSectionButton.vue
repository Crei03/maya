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
        class="group flex w-full flex-col items-center justify-center gap-3 rounded-2xl border p-6 text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)]"
        :class="active
            ? 'border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] shadow-sm'
            : 'border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:border-[var(--maya-primary)] hover:bg-[var(--maya-hover-surface)] shadow-sm hover:shadow-md'"
        @click="$emit('click')"
    >
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] transition-transform duration-200 group-hover:scale-110">
            <font-awesome-icon :icon="icon" class="text-3xl" />
        </div>
        <span class="text-base font-semibold text-[var(--maya-text-main)] transition-colors group-hover:text-[var(--maya-primary)] sm:text-lg">
            {{ label }}
        </span>
    </button>
</template>
