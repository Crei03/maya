<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
import { 
    faBuilding, 
    faPlay, 
    faPause, 
    faBox,
    faPlus,
    faChartLine,
    faTruck,
    faCheck,
    faMoneyBill,
    faTriangleExclamation,
    faShieldAlt,
    faClock
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recentAudits: {
        type: Array,
        default: () => [],
    },
});

// --- Opciones para Gráfico de Crecimiento (12 meses) ---
const growthChartOptions = computed(() => ({
    chart: {
        id: 'tenants-growth',
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
    },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#4f46e5'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [20, 100],
        },
    },
    xaxis: {
        categories: props.stats.charts?.growth?.labels || [],
        labels: { style: { fontSize: '11px', colors: '#6b7280' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            formatter: (val) => Math.round(val),
            style: { fontSize: '11px', colors: '#6b7280' },
        },
    },
    dataLabels: { enabled: false },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
    tooltip: { y: { formatter: (val) => `${val} paqueterías` } },
}));

const growthChartSeries = computed(() => [
    {
        name: 'Nuevas Paqueterías',
        data: props.stats.charts?.growth?.series || [],
    },
]);

// --- Opciones para Gráfico de Volumen de Paquetes (6 meses) ---
const shipmentChartOptions = computed(() => ({
    chart: {
        id: 'shipments-volume',
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '45%',
            distributed: false,
        },
    },
    colors: ['#10b981'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.stats.charts?.shipments?.labels || [],
        labels: { style: { fontSize: '11px', colors: '#6b7280' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            formatter: (val) => Math.round(val),
            style: { fontSize: '11px', colors: '#6b7280' },
        },
    },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
    tooltip: { y: { formatter: (val) => `${val} paquetes` } },
}));

const shipmentChartSeries = computed(() => [
    {
        name: 'Volumen Global',
        data: props.stats.charts?.shipments?.series || [],
    },
]);

// --- Opciones para Gráfico Donut de Estados ---
const statusDonutOptions = computed(() => ({
    chart: {
        id: 'tenants-status',
        fontFamily: 'inherit',
    },
    labels: props.stats.charts?.status_distribution?.labels || ['Activas', 'Pausadas', 'Inactivas'],
    colors: ['#10b981', '#f59e0b', '#ef4444'],
    legend: { position: 'bottom', fontSize: '12px' },
    dataLabels: { enabled: true, dropShadow: { enabled: false } },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        fontSize: '13px',
                        color: '#6b7280',
                        formatter: () => `${props.stats.total_tenants || 0}`,
                    },
                },
            },
        },
    },
}));

const statusDonutSeries = computed(() => {
    return props.stats.charts?.status_distribution?.series || [0, 0, 0];
});
</script>

<template>
    <Head title="Panel SaaS - Super Admin" />

    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Panel de Control SaaS
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Visión global del rendimiento, empresas registradas y volumen del sistema.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('Management.tenants.index')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-sm transition"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                        Nueva Paquetería
                    </Link>
                </div>
            </div>

            <!-- Stats Grid: 6 Cards de KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <!-- Total Paqueterías -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400">
                            <FontAwesomeIcon :icon="faBuilding" class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Empresas</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_tenants }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ stats.active_tenants }} activas &bull; {{ stats.paused_tenants }} pausadas
                        </p>
                    </div>
                </div>

                <!-- Activas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-green-50 dark:bg-green-900/40 text-green-600 dark:text-green-400">
                            <FontAwesomeIcon :icon="faPlay" class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-semibold text-green-600 uppercase tracking-wider">Activas</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.active_tenants }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Operando actualmente</p>
                    </div>
                </div>

                <!-- Paquetes Totales Globales -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                            <FontAwesomeIcon :icon="faBox" class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">Total Paquetes</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_shipments }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Histórico global</p>
                    </div>
                </div>

                <!-- Paquetes Este Mes -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400">
                            <FontAwesomeIcon :icon="faTruck" class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-semibold text-purple-600 uppercase tracking-wider">Mes Actual</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.month_shipments }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Registrados este mes</p>
                    </div>
                </div>

                <!-- Entregas Exitosas Mes -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400">
                            <FontAwesomeIcon :icon="faCheck" class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Entregas</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.month_delivered }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Entregados con éxito</p>
                    </div>
                </div>

                <!-- Revenue Mensual Potencial -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400">
                            <FontAwesomeIcon :icon="faMoneyBill" class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">MRR Est.</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            ${{ Number(stats.potential_revenue || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ingresos por planes</p>
                    </div>
                </div>
            </div>

            <!-- Gráficos: Crecimiento y Volumen -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Crecimiento de Paqueterías (12 meses) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                Crecimiento de Paqueterías
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nuevas empresas registradas por mes (últimos 12 meses)
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                            <FontAwesomeIcon :icon="faChartLine" />
                            Análisis Anual
                        </span>
                    </div>
                    <div class="min-h-[260px]">
                        <VueApexCharts
                            type="area"
                            height="260"
                            :options="growthChartOptions"
                            :series="growthChartSeries"
                        />
                    </div>
                </div>

                <!-- Distribución por Estado -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Distribución de Estados
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Estado operativo de paqueterías registradas
                        </p>
                    </div>
                    <div class="flex items-center justify-center min-h-[260px]">
                        <VueApexCharts
                            type="donut"
                            height="260"
                            :options="statusDonutOptions"
                            :series="statusDonutSeries"
                        />
                    </div>
                </div>
            </div>

            <!-- Gráfico de Volumen de Envíos Globales -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Volumen Global de Paquetes
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Cantidad consolidada de envíos creados en todos los clientes por mes
                        </p>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        Últimos 6 meses
                    </span>
                </div>
                <div class="min-h-[240px]">
                    <VueApexCharts
                        type="bar"
                        height="240"
                        :options="shipmentChartOptions"
                        :series="shipmentChartSeries"
                    />
                </div>
            </div>

            <!-- Sección de Alertas: Baja Actividad -->
            <div v-if="stats.low_activity_tenants?.length > 0" class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900/40 dark:bg-amber-950/20">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300">
                        <FontAwesomeIcon :icon="faTriangleExclamation" class="text-base" />
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                            Atención: Paqueterías con Baja Actividad
                        </h4>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                            Las siguientes paqueterías están activas pero no han registrado ningún paquete en los últimos 30 días.
                        </p>

                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <div 
                                v-for="t in stats.low_activity_tenants" 
                                :key="t.id"
                                class="rounded-xl border border-amber-200/80 bg-white dark:bg-gray-800 p-3 shadow-2xs"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-gray-900 dark:text-white">{{ t.name }}</span>
                                    <span class="font-mono text-[10px] text-gray-400">@{{ t.slug }}</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1 truncate">{{ t.contact_email || 'Sin correo de contacto' }}</p>
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">Registrada el {{ t.created_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablas Inferiores: Paqueterías Recientes y Auditoría -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Paqueterías Recientes -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            Paqueterías Recientes
                        </h2>
                        <Link 
                            :href="route('Management.tenants.index')"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                        >
                            Ver todas &rarr;
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500">
                                <tr>
                                    <th class="px-5 py-3 text-left font-medium">Nombre / Slug</th>
                                    <th class="px-5 py-3 text-left font-medium">Plan</th>
                                    <th class="px-5 py-3 text-left font-medium">Envíos</th>
                                    <th class="px-5 py-3 text-left font-medium">Estado</th>
                                    <th class="px-5 py-3 text-left font-medium">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="tenant in stats.recent_tenants" :key="tenant.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ tenant.name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ tenant.slug }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                        {{ tenant.plan_name }}
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ tenant.shipments_count }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300': tenant.status === 'active',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300': tenant.status === 'paused',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': tenant.status !== 'active' && tenant.status !== 'paused'
                                            }"
                                        >
                                            {{ tenant.status === 'active' ? 'Activa' : tenant.status === 'paused' ? 'Pausada' : tenant.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-500">
                                        {{ tenant.created_at }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Última Actividad de Auditoría -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <FontAwesomeIcon :icon="faShieldAlt" class="text-gray-400" />
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">
                                Auditoría Reciente
                            </h2>
                        </div>
                        <span class="text-xs text-gray-400">Últimos eventos globales</span>
                    </div>
                    <div class="p-4">
                        <div v-if="recentAudits.length === 0" class="py-8 text-center text-xs text-gray-400">
                            No hay eventos de auditoría registrados.
                        </div>
                        <div v-else class="space-y-3">
                            <div 
                                v-for="log in recentAudits" 
                                :key="log.id"
                                class="flex items-center justify-between rounded-xl border border-gray-100 dark:border-gray-700 p-2.5 text-xs hover:bg-gray-50/50 dark:hover:bg-gray-700/20"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono text-[10px]">
                                        {{ log.action.slice(0, 2).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            <span class="uppercase font-semibold text-indigo-600 dark:text-indigo-400">{{ log.action }}</span> en {{ log.entity_type }}
                                        </p>
                                        <p v-if="log.ip_address" class="text-[10px] text-gray-400 font-mono">
                                            IP: {{ log.ip_address }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-[10px] text-gray-400 flex items-center gap-1 shrink-0">
                                    <FontAwesomeIcon :icon="faClock" class="text-[9px]" />
                                    {{ log.created_at }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ManagementLayout>
</template>

