<script setup lang="jsx">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    kpis: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: ''
        })
    }
});

// Calculate current week period (Monday to Saturday)
const weekPeriod = computed(() => {
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

    // Calculate Monday (if today is Sunday, go back 6 days; otherwise go back dayOfWeek - 1 days)
    const monday = new Date(today);
    const daysFromMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    monday.setDate(today.getDate() - daysFromMonday);

    // Calculate Saturday (Monday + 5 days)
    const saturday = new Date(monday);
    saturday.setDate(monday.getDate() + 5);

    // Format dates as DD/MM/YYYY
    const formatDate = (date) => {
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    return {
        start: formatDate(monday),
        end: formatDate(saturday)
    };
});

// Stats cards data
const stats = computed(() => [
    {
        name: 'Total Envíos',
        value: props.kpis.stats?.total_shipments || 0,
        icon: PackageIcon,
        color: 'primary'
    },
    {
        name: 'Envíos Hoy',
        value: props.kpis.stats?.shipments_today || 0,
        icon: TruckIcon,
        color: 'success'
    },
    {
        name: 'Mensajeros Activos',
        value: props.kpis.stats?.active_messengers || 0,
        icon: UsersIcon,
        color: 'warning'
    },
    {
        name: 'Incidentes Abiertos',
        value: props.kpis.stats?.open_incidents || 0,
        icon: AlertIcon,
        color: 'danger'
    }
]);

// Color mapping for Tailwind classes
const colorClasses = {
    primary: {
        bg: 'bg-[var(--maya-primary)]',
        bgLight: 'bg-[var(--maya-primary-alpha)]',
        text: 'text-[var(--maya-primary)]'
    },
    success: {
        bg: 'bg-[var(--maya-success)]',
        bgLight: 'bg-[var(--maya-success-alpha)]',
        text: 'text-[var(--maya-success)]'
    },
    warning: {
        bg: 'bg-[var(--maya-warning)]',
        bgLight: 'bg-[var(--maya-warning-alpha)]',
        text: 'text-[var(--maya-warning)]'
    },
    danger: {
        bg: 'bg-[var(--maya-danger)]',
        bgLight: 'bg-[var(--maya-danger-alpha)]',
        text: 'text-[var(--maya-danger)]'
    }
};

// Delivery Rate Chart Options
const deliveryRateChartOptions = computed(() => ({
    chart: {
        type: 'radialBar',
        height: 350,
    },
    plotOptions: {
        radialBar: {
            startAngle: -90,
            endAngle: 90,
            track: {
                background: 'var(--maya-border)',
                strokeWidth: '97%',
                margin: 5,
            },
            dataLabels: {
                name: {
                    show: true,
                    fontSize: '16px',
                    color: 'var(--maya-text-muted)',
                    offsetY: 20
                },
                value: {
                    show: true,
                    fontSize: '36px',
                    fontWeight: 'bold',
                    color: 'var(--maya-primary)',
                    offsetY: -20,
                    formatter: function (val) {
                        return val + '%';
                    }
                }
            }
        }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shade: 'dark',
            type: 'horizontal',
            gradientToColors: ['var(--maya-success)'],
            stops: [0, 100]
        }
    },
    stroke: {
        lineCap: 'round'
    },
    labels: ['Tasa de Entrega']
}));

const deliveryRateSeries = computed(() => [
    props.kpis.delivery_rate?.value || 0
]);

// CSAT Chart Options
const csatChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350,
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
            borderRadius: 8,
            columnWidth: '60%',
            distributed: true
        }
    },
    colors: ['var(--maya-danger)', 'var(--maya-warning)', '#eab308', '#84cc16', 'var(--maya-success)'],
    dataLabels: {
        enabled: true,
        style: {
            colors: ['#fff']
        }
    },
    legend: {
        show: false
    },
    xaxis: {
        categories: ['1★', '2★', '3★', '4★', '5★'],
        labels: {
            style: {
                fontSize: '14px',
                fontWeight: 'bold',
                colors: 'var(--maya-text-main)'
            }
        }
    },
    yaxis: {
        title: {
            text: 'Cantidad de Calificaciones',
            style: {
                color: 'var(--maya-text-muted)'
            }
        },
        labels: {
            style: {
                colors: 'var(--maya-text-muted)'
            }
        }
    },
    title: {
        text: 'Distribución de Calificaciones',
        align: 'center',
        style: {
            fontSize: '16px',
            fontWeight: 'bold',
            color: 'var(--maya-text-main)'
        }
    }
}));

const csatSeries = computed(() => [{
    name: 'Calificaciones',
    data: [
        props.kpis.satisfaction?.distribution?.['1'] || 0,
        props.kpis.satisfaction?.distribution?.['2'] || 0,
        props.kpis.satisfaction?.distribution?.['3'] || 0,
        props.kpis.satisfaction?.distribution?.['4'] || 0,
        props.kpis.satisfaction?.distribution?.['5'] || 0,
    ]
}]);

// By Messenger Chart Options
const byMessengerChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350,
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            dataLabels: {
                position: 'top'
            }
        }
    },
    colors: ['var(--maya-primary)'],
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return val + '%';
        },
        offsetX: 20,
        style: {
            fontSize: '12px',
            colors: ['var(--maya-text-main)']
        }
    },
    xaxis: {
        categories: props.kpis.by_messenger?.map(m => m.messenger_name) || [],
        max: 100,
        title: {
            text: 'Tasa de Entrega (%)',
            style: {
                color: 'var(--maya-text-muted)'
            }
        },
        labels: {
            style: {
                colors: 'var(--maya-text-muted)'
            }
        }
    },
    yaxis: {
        title: {
            text: 'Mensajero',
            style: {
                color: 'var(--maya-text-muted)'
            }
        },
        labels: {
            style: {
                colors: 'var(--maya-text-main)'
            }
        }
    },
    title: {
        text: 'Rendimiento por Mensajero',
        align: 'center',
        style: {
            fontSize: '16px',
            fontWeight: 'bold',
            color: 'var(--maya-text-main)'
        }
    }
}));

const byMessengerSeries = computed(() => [{
    name: 'Tasa de Entrega',
    data: props.kpis.by_messenger?.map(m => m.rate) || []
}]);

// Icon components
function PackageIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
    );
}

function TruckIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
        </svg>
    );
}

function UsersIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
        </svg>
    );
}

function AlertIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
    );
}
</script>

<template>
    <Head title="Dashboard Administrativo" />

    <AdminLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-[var(--maya-text-main)] sm:truncate sm:text-3xl sm:tracking-tight">
                        Panel de Control
                    </h2>
                    <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                        Período: {{ weekPeriod.start }} - {{ weekPeriod.end }}
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="stat in stats"
                    :key="stat.name"
                    class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6"
                >
                    <dt>
                        <div :class="`absolute rounded-md ${colorClasses[stat.color].bg} p-3`">
                            <component :is="stat.icon" class="h-6 w-6 text-white" aria-hidden="true" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">
                            {{ stat.name }}
                        </p>
                    </dt>
                    <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                        <p class="text-2xl font-semibold text-[var(--maya-text-main)]">
                            {{ stat.value }}
                        </p>
                    </dd>
                </div>
            </div>

            <!-- KPIs Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Delivery Rate Card -->
                <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-medium leading-6 text-[var(--maya-text-main)]">
                            {{ kpis.delivery_rate?.label || 'Tasa de Entregas' }}
                        </h3>
                        <div class="mt-4">
                            <VueApexCharts
                                type="radialBar"
                                height="350"
                                :options="deliveryRateChartOptions"
                                :series="deliveryRateSeries"
                            />
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-sm text-[var(--maya-text-muted)]">Total</p>
                                <p class="text-lg font-semibold text-[var(--maya-text-main)]">
                                    {{ kpis.delivery_rate?.total || 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-[var(--maya-success)]">Exitosas</p>
                                <p class="text-lg font-semibold text-[var(--maya-success)]">
                                    {{ kpis.delivery_rate?.successful || 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-[var(--maya-danger)]">Fallidas</p>
                                <p class="text-lg font-semibold text-[var(--maya-danger)]">
                                    {{ kpis.delivery_rate?.failed || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CSAT Card -->
                <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium leading-6 text-[var(--maya-text-main)]">
                                {{ kpis.satisfaction?.label || 'Satisfacción del Cliente' }}
                            </h3>
                            <div class="text-right">
                                <p class="text-3xl font-bold text-[var(--maya-primary)]">
                                    {{ kpis.satisfaction?.value || 0 }}<span class="text-lg text-[var(--maya-text-muted)]">/5</span>
                                </p>
                                <p class="text-sm text-[var(--maya-text-muted)]">
                                    {{ kpis.satisfaction?.total_ratings || 0 }} calificaciones
                                </p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <VueApexCharts
                                type="bar"
                                height="300"
                                :options="csatChartOptions"
                                :series="csatSeries"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- By Messenger Chart -->
            <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-[var(--maya-text-main)]">
                        Rendimiento por Mensajero
                    </h3>
                    <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                        Tasa de entregas exitosas por transportista
                    </p>
                    <div class="mt-6">
                        <VueApexCharts
                            type="bar"
                            height="350"
                            :options="byMessengerChartOptions"
                            :series="byMessengerSeries"
                        />
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-[var(--maya-text-main)]">
                        Detalle por Mensajero
                    </h3>
                    <div class="mt-4 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <table class="min-w-full divide-y divide-[var(--maya-border)]">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-[var(--maya-text-main)] sm:pl-0">
                                                Mensajero
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-[var(--maya-text-main)]">
                                                Total
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-[var(--maya-text-main)]">
                                                Exitosas
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-[var(--maya-text-main)]">
                                                Fallidas
                                            </th>
                                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-[var(--maya-text-main)]">
                                                Tasa
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--maya-border)]">
                                        <tr v-for="messenger in kpis.by_messenger" :key="messenger.messenger_id">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-[var(--maya-text-main)] sm:pl-0">
                                                {{ messenger.messenger_name }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-[var(--maya-text-muted)]">
                                                {{ messenger.total }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-[var(--maya-success)]">
                                                {{ messenger.successful }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-[var(--maya-danger)]">
                                                {{ messenger.failed }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-right text-sm">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                                        messenger.rate >= 90
                                                            ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success)] ring-[var(--maya-success)]/20'
                                                            : messenger.rate >= 70
                                                                ? 'bg-[var(--maya-warning-alpha)] text-[var(--maya-warning)] ring-[var(--maya-warning)]/20'
                                                                : 'bg-[var(--maya-danger-alpha)] text-[var(--maya-danger)] ring-[var(--maya-danger)]/20'
                                                    ]"
                                                >
                                                    {{ messenger.rate }}%
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="!kpis.by_messenger?.length">
                                            <td colspan="5" class="py-8 text-center text-sm text-[var(--maya-text-muted)]">
                                                No hay datos disponibles
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
