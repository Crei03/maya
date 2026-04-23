<script setup>
import { computed } from 'vue';
import TextInput from '@/Components/input/TextInput.vue';

const props = defineProps({
    fields: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    'update:modelValue',
    'apply',
    'clear',
]);

const normalizedFields = computed(() => props.fields || []);

const updateField = (key, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
};

const parseSelectValue = (field, event) => {
    if (field.multiple) {
        return Array.from(event.target.selectedOptions).map((option) => (
            field.valueType === 'number' ? Number(option.value) : option.value
        ));
    }

    if (event.target.value === '') {
        return null;
    }

    return field.valueType === 'number' ? Number(event.target.value) : event.target.value;
};
</script>

<template>
    <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div
                v-for="field in normalizedFields"
                :key="field.key"
                class="min-w-0"
                :style="field.colSpan ? { gridColumn: `span ${field.colSpan} / span ${field.colSpan}` } : undefined"
            >
                <TextInput
                    v-if="field.type !== 'select'"
                    :model-value="modelValue[field.key] ?? ''"
                    :type="field.type || 'text'"
                    :placeholder="field.placeholder"
                    class="w-full !bg-[var(--maya-bg-surface)] !text-[var(--maya-text-main)] !border-[var(--maya-border)] placeholder:!text-[var(--maya-text-muted)]"
                    @update:model-value="updateField(field.key, field.valueType === 'number' ? ($event === '' ? null : Number($event)) : $event)"
                />

                <select
                    v-else
                    class="w-full rounded-md border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)]"
                    :value="modelValue[field.key] ?? ''"
                    @change="updateField(field.key, parseSelectValue(field, $event))"
                >
                    <option v-if="field.placeholder !== false" value="">
                        {{ field.placeholder || 'Seleccionar opción' }}
                    </option>
                    <option
                        v-for="option in field.options || []"
                        :key="option[field.optionValue || 'id']"
                        :value="option[field.optionValue || 'id']"
                    >
                        {{ option[field.optionLabel || 'valor'] }}
                    </option>
                </select>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="inline-flex items-center rounded-md bg-[var(--maya-primary)] px-3 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)]"
                @click="emit('apply')"
            >
                Aplicar filtros
            </button>
            <button
                type="button"
                class="inline-flex items-center rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)]"
                @click="emit('clear')"
            >
                Limpiar
            </button>
        </div>
    </div>
</template>
