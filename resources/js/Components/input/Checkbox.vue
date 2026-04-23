<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded-md border-[var(--maya-border)] text-[var(--maya-primary)] shadow-sm focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:focus:ring-[var(--maya-primary)] dark:focus:ring-offset-[var(--maya-bg-base)]"
    />
</template>
