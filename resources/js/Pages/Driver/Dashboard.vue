<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import DriverLayout from '@/Layouts/DriverLayout.vue';

const props = defineProps({
    driver: {
        type: Object,
        required: true,
    },
    kpis: {
        type: Object,
        required: true,
    },
    plans: {
        type: Array,
        default: () => [],
    },
});

const formattedDate = computed(() => {
    return new Intl.DateTimeFormat('es-PA', {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
    }).format(new Date());
});
</script>

<template>
    <Head title="Panel del Transportista" />

    <DriverLayout>
        <div class="space-y-5">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 dark:from-slate-800 dark:via-slate-900 dark:to-indigo-950 text-white p-4.5 rounded-2xl shadow-lg relative overflow-hidden">
                <div class="absolute -right-4 -bottom-6 w-28 h-28 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10">
                    <p class="text-xs uppercase tracking-wider text-indigo-300 font-semibold capitalize">
                        {{ formattedDate }}
                    </p>
                    <h1 class="text-lg font-bold tracking-tight mt-0.5">
                        ¡Hola, {{ driver.name }}! 👋
                    </h1>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                        Revisa tus planes de entrega asignados y gestiona cada parada en ruta.
                    </p>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2.5 px-0.5">
                    Resumen del Día
                </h2>

                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Entregas del Día -->
                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Entregadas</span>
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs">
                                <font-awesome-icon :icon="['fas', 'circle-check']" />
                            </div>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-xl font-black text-slate-900 dark:text-white">
                                {{ kpis.completed_deliveries }}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                / {{ kpis.total_deliveries }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full mt-2 overflow-hidden">
                            <div
                                class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                                :style="{ width: `${kpis.completion_rate}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Pendientes -->
                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Pendientes</span>
                            <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs">
                                <font-awesome-icon :icon="['fas', 'clock']" />
                            </div>
                        </div>
                        <div class="text-xl font-black text-slate-900 dark:text-white">
                            {{ kpis.pending_deliveries }}
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2 truncate">
                            Por entregar hoy
                        </p>
                    </div>

                    <!-- Tasa de Cumplimiento -->
                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Progreso</span>
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs">
                                <font-awesome-icon :icon="['fas', 'chart-line']" />
                            </div>
                        </div>
                        <div class="text-xl font-black text-slate-900 dark:text-white">
                            {{ kpis.completion_rate }}%
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2 truncate">
                            Efectividad diaria
                        </p>
                    </div>

                    <!-- Planes Asignados -->
                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Rutas / Planes</span>
                            <div class="w-6 h-6 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xs">
                                <font-awesome-icon :icon="['fas', 'route']" />
                            </div>
                        </div>
                        <div class="text-xl font-black text-slate-900 dark:text-white">
                            {{ plans.length }}
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2 truncate">
                            {{ kpis.active_plans_count }} activas
                        </p>
                    </div>
                </div>
            </div>

            <!-- Delivery Plans Selection Section -->
            <div>
                <div class="flex items-center justify-between mb-2.5 px-0.5">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Planes de Entrega Asignados
                    </h2>
                    <span class="text-[11px] font-medium text-slate-400">
                        {{ plans.length }} disponibles
                    </span>
                </div>

                <div v-if="plans.length > 0" class="space-y-3">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="p-4 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200/90 dark:border-slate-700/70 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-700 transition-all flex flex-col gap-3"
                    >
                        <!-- Plan Header -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                                    {{ plan.title }}
                                </span>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                    <span v-if="plan.scheduled_date" class="flex items-center gap-1">
                                        <font-awesome-icon :icon="['fas', 'calendar']" class="text-[10px]" />
                                        {{ plan.scheduled_date }}
                                    </span>
                                    <span v-if="plan.warehouse" class="flex items-center gap-1">
                                        <font-awesome-icon :icon="['fas', 'warehouse']" class="text-[10px]" />
                                        {{ plan.warehouse.name }}
                                    </span>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider border" :class="plan.status.badge">
                                {{ plan.status.valor }}
                            </span>
                        </div>

                        <!-- Progress Bar & Details -->
                        <div class="bg-slate-50 dark:bg-slate-900/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-semibold text-slate-700 dark:text-slate-300 text-[11px]">
                                    {{ plan.completed_stops }} de {{ plan.total_stops }} paradas entregadas
                                </span>
                                <span class="font-black text-indigo-600 dark:text-indigo-400 text-xs">
                                    {{ plan.progress }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div
                                    class="bg-indigo-600 dark:bg-indigo-500 h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${plan.progress}%` }"
                                ></div>
                            </div>
                            <div v-if="plan.vehicle" class="mt-2 flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                                <font-awesome-icon :icon="['fas', 'truck']" />
                                <span>Vehículo: {{ plan.vehicle.plate }} ({{ plan.vehicle.model }})</span>
                            </div>
                        </div>

                        <!-- CTA Button to Enter Plan Tasks -->
                        <Link
                            :href="route('driver.tasks', plan.id)"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-semibold text-xs flex items-center justify-center gap-2 shadow-sm transition-colors active:scale-[0.99]"
                        >
                            <span>Abrir Paradas y Entregas</span>
                            <font-awesome-icon :icon="['fas', 'arrow-right']" class="text-[11px]" />
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-10 px-4 bg-white dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700/60">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center text-lg mb-3">
                        <font-awesome-icon :icon="['fas', 'clipboard-check']" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                        No hay planes asignados
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">
                        Actualmente no tienes planes de entrega activos asignados a tu cuenta. Contacta a tu gestor de operaciones.
                    </p>
                </div>
            </div>
        </div>
    </DriverLayout>
</template>
