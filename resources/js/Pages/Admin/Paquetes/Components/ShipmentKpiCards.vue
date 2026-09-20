<script setup>
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            by_status: {},
            summary: {},
        }),
    },
    activeStatus: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select-status']);

const totalShipments = computed(() => Number(props.stats?.total || 0));

const cards = computed(() => {
    const total = totalShipments.value;
    const byStatus = props.stats?.by_status || {};
    const summary = props.stats?.summary || {};

    const pendingCount = Number(summary.pending ?? (byStatus.PENDIENTE || 0));
    const assignedCount = Number(summary.assigned ?? (byStatus.ASIGNADO || 0));
    const inTransitCount = Number(summary.in_transit ?? (byStatus.EN_TRANSITO || 0));
    const inTransitTotal = Number(summary.active_in_transit ?? (assignedCount + inTransitCount));
    const deliveredCount = Number(summary.delivered ?? (byStatus.ENTREGADO || 0));
    const failedCount = Number(byStatus.FALLIDO || 0);
    const returnedCount = Number(byStatus.DEVUELTO || 0);
    const cancelledCount = Number(byStatus.CANCELADO || 0);
    const issuesTotal = Number(summary.issues ?? (failedCount + returnedCount + cancelledCount));

    return [
        {
            key: '',
            label: 'Total Paquetes',
            count: total,
            icon: ['fas', 'boxes-stacked'],
            iconColor: 'text-[var(--maya-primary)]',
            iconBg: 'bg-[var(--maya-primary-alpha)]',
            ringClass: 'ring-2 ring-[var(--maya-primary)] border-transparent',
            activeBarBg: 'bg-[var(--maya-primary)]',
        },
        {
            key: 'PENDIENTE',
            label: 'Pendientes',
            count: pendingCount,
            icon: ['fas', 'clock'],
            iconColor: 'text-amber-500 dark:text-amber-400',
            iconBg: 'bg-amber-500/10 dark:bg-amber-400/10',
            ringClass: 'ring-2 ring-amber-500 border-transparent',
            activeBarBg: 'bg-amber-500',
        },
        {
            key: 'EN_TRANSITO,ASIGNADO',
            label: 'En Tránsito',
            count: inTransitTotal,
            icon: ['fas', 'truck-fast'],
            iconColor: 'text-indigo-500 dark:text-indigo-400',
            iconBg: 'bg-indigo-500/10 dark:bg-indigo-400/10',
            ringClass: 'ring-2 ring-indigo-500 border-transparent',
            activeBarBg: 'bg-indigo-500',
        },
        {
            key: 'ENTREGADO',
            label: 'Entregados',
            count: deliveredCount,
            icon: ['fas', 'circle-check'],
            iconColor: 'text-[var(--maya-success)]',
            iconBg: 'bg-[var(--maya-success-alpha)]',
            ringClass: 'ring-2 ring-[var(--maya-success)] border-transparent',
            activeBarBg: 'bg-[var(--maya-success)]',
        },
        {
            key: 'FALLIDO,DEVUELTO,CANCELADO',
            label: 'Incidencias',
            count: issuesTotal,
            icon: ['fas', 'triangle-exclamation'],
            iconColor: 'text-[var(--maya-danger)]',
            iconBg: 'bg-[var(--maya-danger-alpha)]',
            ringClass: 'ring-2 ring-[var(--maya-danger)] border-transparent',
            activeBarBg: 'bg-[var(--maya-danger)]',
        },
    ];
});

const isCardActive = (card) => {
    const current = (props.activeStatus || '').trim().toUpperCase();
    if (!card.key) {
        return current === '';
    }
    if (current === card.key.toUpperCase()) {
        return true;
    }
    const keys = card.key.toUpperCase().split(',').map((k) => k.trim());
    return keys.includes(current);
};

const handleCardClick = (card) => {
    if (!card.key) {
        emit('select-status', '');
        return;
    }

    if (isCardActive(card)) {
        // Toggle off if already selected
        emit('select-status', '');
    } else {
        emit('select-status', card.key);
    }
};
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div
            v-for="card in cards"
            :key="card.label"
            class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-[var(--maya-bg-surface)] p-4 shadow-sm transition-all duration-200 cursor-pointer select-none hover:-translate-y-0.5 hover:shadow-md"
            :class="[
                isCardActive(card)
                    ? card.ringClass + ' bg-[var(--maya-bg-surface)] shadow-md'
                    : 'border-[var(--maya-border)] hover:border-slate-300 dark:hover:border-slate-600',
                loading ? 'opacity-70' : '',
            ]"
            :title="isCardActive(card) ? 'Filtro activo (clic para quitar)' : `Filtrar por ${card.label}`"
            role="button"
            tabindex="0"
            @click="handleCardClick(card)"
            @keydown.enter="handleCardClick(card)"
            @keydown.space.prevent="handleCardClick(card)"
        >
            <!-- Accent indicator top bar when active -->
            <div
                v-if="isCardActive(card)"
                class="absolute left-0 right-0 top-0 h-1"
                :class="card.activeBarBg"
            />

            <!-- Top row: Icon -->
            <div class="flex items-center">
                <span
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition-transform group-hover:scale-105"
                    :class="[card.iconBg, card.iconColor]"
                >
                    <font-awesome-icon :icon="card.icon" class="text-sm" />
                </span>
            </div>

            <!-- Metric value & Label -->
            <div class="mt-3">
                <div class="font-mono text-2xl font-black tracking-tight text-[var(--maya-text-main)] sm:text-3xl">
                    {{ card.count.toLocaleString('es-ES') }}
                </div>
                <div class="mt-1 truncate text-xs font-bold text-[var(--maya-text-main)]">
                    {{ card.label }}
                </div>
            </div>

            <!-- Bottom decorative hover bar -->
            <div
                class="absolute bottom-0 left-0 right-0 h-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                :class="card.activeBarBg"
            />
        </div>
    </div>
</template>
