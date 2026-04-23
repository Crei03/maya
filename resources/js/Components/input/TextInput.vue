<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
        v-model="model"
        ref="input"
    />
</template>
