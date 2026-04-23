<script setup>
import { computed } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    visibleColumns: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyText: {
        type: String,
        default: 'No hay registros para mostrar.',
    },
    pagination: {
        type: Object,
        default: null,
    },
    perPage: {
        type: Number,
        default: 15,
    },
    minPerPage: {
        type: Number,
        default: 1,
    },
    maxPerPage: {
        type: Number,
        default: 200,
    },
});

const emit = defineEmits(['update:perPage', 'changePage']);

const activeColumns = computed(() => {
    if (!props.visibleColumns.length) {
        return props.columns;
    }

    return props.columns.filter((column) => props.visibleColumns.includes(column.key));
});

const paginationMeta = computed(() => props.pagination || null);

const canGoPrevious = computed(() => {
    if (!paginationMeta.value) {
        return false;
    }

    return Number(paginationMeta.value.current_page || 1) > 1;
});

const canGoNext = computed(() => {
    if (!paginationMeta.value) {
        return false;
    }

    return Number(paginationMeta.value.current_page || 1) < Number(paginationMeta.value.last_page || 1);
});

const onPerPageChange = (event) => {
    const raw = Number(event.target.value);

    if (!Number.isFinite(raw)) {
        return;
    }

    const nextValue = Math.min(props.maxPerPage, Math.max(props.minPerPage, Math.trunc(raw)));
    emit('update:perPage', nextValue);
};

const goToPreviousPage = () => {
    if (!canGoPrevious.value) {
        return;
    }

    emit('changePage', Number(paginationMeta.value.current_page) - 1);
};

const goToNextPage = () => {
    if (!canGoNext.value) {
        return;
    }

    emit('changePage', Number(paginationMeta.value.current_page) + 1);
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] shadow-sm">
        <div v-if="loading" class="border-b border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-sm text-[var(--maya-text-muted)]">
            Cargando datos...
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--maya-border)]">
                <thead class="bg-[var(--maya-hover-surface)]">
                    <tr>
                        <th
                            v-for="column in activeColumns"
                            :key="column.key"
                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--maya-text-muted)]"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--maya-border)] bg-[var(--maya-bg-surface)]">
                    <tr v-if="!loading && !rows.length">
                        <td
                            :colspan="activeColumns.length || 1"
                            class="px-4 py-8 text-center text-sm text-[var(--maya-text-muted)] bg-[var(--maya-bg-surface)]"
                        >
                            {{ emptyText }}
                        </td>
                    </tr>

                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="hover:bg-[var(--maya-hover-surface)]"
                    >
                        <td
                            v-for="column in activeColumns"
                            :key="`${row.id}-${column.key}`"
                            class="px-4 py-3 text-sm text-[var(--maya-text-main)]"
                        >
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] ?? '-' }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="paginationMeta"
            class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--maya-border)] px-4 py-3"
        >
            <p class="text-xs text-[var(--maya-text-muted)]">
                Mostrando {{ paginationMeta.from || 0 }}-{{ paginationMeta.to || 0 }} de {{ paginationMeta.total || 0 }}
            </p>

            <div class="flex flex-wrap items-center gap-2">
                <label class="text-xs font-medium text-[var(--maya-text-muted)]" for="per-page-input">
                    Resultados por pagina
                </label>
                <input
                    id="per-page-input"
                    type="number"
                    class="w-24 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2 py-1 text-xs text-[var(--maya-text-main)]"
                    :min="minPerPage"
                    :max="maxPerPage"
                    :value="perPage"
                    @change="onPerPageChange"
                >

                <button
                    type="button"
                    class="rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--maya-text-main)] disabled:opacity-50"
                    :disabled="!canGoPrevious"
                    @click="goToPreviousPage"
                >
                    Anterior
                </button>

                <span class="text-xs text-[var(--maya-text-muted)]">
                    Pagina {{ paginationMeta.current_page || 1 }} de {{ paginationMeta.last_page || 1 }}
                </span>

                <button
                    type="button"
                    class="rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--maya-text-main)] disabled:opacity-50"
                    :disabled="!canGoNext"
                    @click="goToNextPage"
                >
                    Siguiente
                </button>
            </div>
        </div>
    </div>
</template>
