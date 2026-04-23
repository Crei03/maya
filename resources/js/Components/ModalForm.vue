<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/input/TextInput.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    fields: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    submitLabel: {
        type: String,
        default: 'Guardar',
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
});

const emit = defineEmits(['close', 'submit', 'update:modelValue']);

const updateField = (key, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
};

const normalizedFields = computed(() => props.fields || []);
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-sm text-[var(--maya-text-muted)]">
                {{ description }}
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="field in normalizedFields"
                    :key="field.key"
                    class="min-w-0"
                    :style="field.colSpan ? { gridColumn: `span ${field.colSpan} / span ${field.colSpan}` } : undefined"
                >
                    <InputLabel :value="field.label" />

                    <template v-if="field.type === 'select'">
                        <select
                            class="mt-1 block w-full rounded-md border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)]"
                            :value="modelValue[field.key] ?? ''"
                            @change="updateField(field.key, field.multiple ? Array.from($event.target.selectedOptions).map((option) => option.value) : ($event.target.value === '' ? null : (field.valueType === 'number' ? Number($event.target.value) : $event.target.value)))"
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
                    </template>

                    <template v-else>
                        <TextInput
                            :model-value="modelValue[field.key]"
                            :type="field.type || 'text'"
                            class="mt-1 block w-full !bg-[var(--maya-bg-surface)] !text-[var(--maya-text-main)] !border-[var(--maya-border)] placeholder:!text-[var(--maya-text-muted)]"
                            :placeholder="field.placeholder"
                            @update:model-value="updateField(field.key, field.valueType === 'number' ? ($event === '' ? null : Number($event)) : $event)"
                        />
                    </template>

                    <InputError class="mt-1" :message="errors[field.key]?.[0]" />
                </div>
            </div>

            <InputError class="mt-3" :message="errors.form?.[0]" />

            <div class="mt-6 flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                    @click="$emit('close')"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-md bg-[var(--maya-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--maya-primary-dark)] disabled:opacity-60"
                    :disabled="loading"
                    @click="$emit('submit')"
                >
                    {{ loading ? 'Guardando...' : submitLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
