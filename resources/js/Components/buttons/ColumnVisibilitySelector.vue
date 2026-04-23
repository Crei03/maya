<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['save']);
const open = ref(false);
const draftColumns = ref([]);

const selectedCount = computed(() => draftColumns.value.length);

watch(
    () => props.modelValue,
    (value) => {
        if (!open.value) {
            draftColumns.value = [...value];
        }
    },
    { immediate: true, deep: true }
);

const openModal = () => {
    draftColumns.value = [...props.modelValue];
    open.value = true;
};

const closeModal = () => {
    open.value = false;
};

const toggleColumn = (key) => {
    const exists = draftColumns.value.includes(key);

    if (exists && draftColumns.value.length === 1) {
        return;
    }

    draftColumns.value = exists
        ? draftColumns.value.filter((value) => value !== key)
        : [...draftColumns.value, key];
};

const saveColumns = () => {
    emit('save', [...draftColumns.value]);
    closeModal();
};

const isSelected = (key) => draftColumns.value.includes(key);

</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)]"
            @click="openModal"
        >
            <font-awesome-icon :icon="['fas', 'table-columns']" />
            Columnas
            <span class="rounded-full bg-[var(--maya-primary-alpha)] px-2 py-0.5 text-[10px] font-bold text-[var(--maya-primary)]">
                {{ modelValue.length }}
            </span>
        </button>

        <div
            v-if="open"
            class="fixed inset-0 z-40 flex items-center justify-center bg-[color:rgba(3,31,65,0.28)] p-4 backdrop-blur-sm dark:bg-[color:rgba(0,0,0,0.45)]"
            @click="closeModal"
        >
            <div
                class="w-full max-w-3xl rounded-3xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6 shadow-[0_24px_54px_rgba(24,28,32,0.18)]"
                @click.stop
            >
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--maya-text-muted)]">Columnas visibles</p>
                        <h3 class="mt-1 text-lg font-semibold text-[var(--maya-text-main)]">Configura tu vista</h3>
                        <p class="mt-1 text-sm leading-5 text-[var(--maya-text-muted)]">
                            Selecciona las columnas que deseas mostrar. Esta configuracion se guarda por usuario.
                        </p>
                    </div>
                </div>
                <div class="mb-5 flex items-center justify-between rounded-2xl bg-[var(--maya-bg-base)] px-4 py-3 text-xs text-[var(--maya-text-main)]">
                    <span class="font-medium">{{ selectedCount }} de {{ columns.length }} columnas activas</span>
                    <span class="rounded-full bg-[var(--maya-primary-alpha)] px-2.5 py-1 font-semibold text-[var(--maya-primary)]">
                        Vista personalizada
                    </span>
                </div>
                
                <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label
                        v-for="column in columns"
                        :key="column.key"
                        class="group flex cursor-pointer items-center justify-between gap-3 rounded-2xl border px-3 py-3 transition"
                        :class="isSelected(column.key)
                            ? 'border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)]'
                            : 'border-[var(--maya-border)] bg-[var(--maya-bg-base)] hover:bg-[var(--maya-hover-surface)]'"
                    >
                        <span class="text-sm font-medium text-[var(--maya-text-main)]">{{ column.label }}</span>

                        <input
                            type="checkbox"
                            class="sr-only"
                            :checked="isSelected(column.key)"
                            :disabled="draftColumns.length === 1 && isSelected(column.key)"
                            @change="toggleColumn(column.key)"
                        >

                        <span
                            class="relative inline-flex h-7 w-14 items-center rounded-full border border-transparent p-1 transition-all duration-300 ease-in-out overflow-hidden"
                            :class="[
                                isSelected(column.key)
                                    ? 'bg-[var(--maya-primary)]'
                                    : 'bg-[var(--maya-text-muted)]/35',
                                draftColumns.length === 1 && isSelected(column.key) ? 'opacity-60' : ''
                            ]"
                        >
                            <span
                                class="absolute top-1 inline-flex h-5 w-5 rounded-full bg-white shadow-md transition-all duration-300 ease-in-out will-change-transform dark:bg-[var(--maya-bg-surface)]"
                                :style="{
                                    left: isSelected(column.key) ? 'calc(100% - 1.25rem)' : '0.25rem'
                                }"
                            />
                        </span>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-[color:rgba(116,119,127,0.26)] px-5 py-2.5 text-sm font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="closeModal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-[var(--maya-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[var(--maya-primary-dark)] disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="loading || draftColumns.length === 0"
                        @click="saveColumns"
                    >
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
