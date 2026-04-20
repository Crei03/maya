<script setup lang="jsx">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';

// Props (mock data para UI)
const props = defineProps({
    conciliacion: {
        type: Object,
        default: () => ({
            fecha: '2026-03-10',
            total_paquetes: 156,
            entregados: 134,
            pendientes: 12,
            devueltos: 10
        })
    },
    mensajeros: {
        type: Array,
        default: () => [
            {
                id: 1,
                nombre: 'Carlos Méndez',
                total_asignados: 45,
                entregados: 42,
                pendientes: 2,
                devueltos: 1,
                estado: 'pendiente'
            },
            {
                id: 2,
                nombre: 'María González',
                total_asignados: 38,
                entregados: 35,
                pendientes: 1,
                devueltos: 2,
                estado: 'pendiente'
            },
            {
                id: 3,
                nombre: 'Juan Pérez',
                total_asignados: 42,
                entregados: 38,
                pendientes: 3,
                devueltos: 1,
                estado: 'liquidado'
            },
            {
                id: 4,
                nombre: 'Ana Rodríguez',
                total_asignados: 31,
                entregados: 29,
                pendientes: 1,
                devueltos: 1,
                estado: 'pendiente'
            }
        ]
    },
    paquetesPendientes: {
        type: Array,
        default: () => [
            { id: 1, tracking: 'MAYA001234', cliente: 'Juan Martínez', direccion: 'Calle 50 #12-34', motivo: null },
            { id: 2, tracking: 'MAYA001235', cliente: 'María López', direccion: 'Av. Balboa #45', motivo: null },
            { id: 3, tracking: 'MAYA001236', cliente: 'Pedro Sánchez', direccion: 'Carrera 7 #89-12', motivo: 'Dirección incorrecta' },
            { id: 4, tracking: 'MAYA001237', cliente: 'Laura Torres', direccion: 'Calle 100 #23-45', motivo: 'Cliente no disponible' }
        ]
    },
    motivosDevolucion: {
        type: Array,
        default: () => [
            { id: 'direccion_incorrecta', label: 'Dirección incorrecta' },
            { id: 'cliente_no_disponible', label: 'Cliente no disponible' },
            { id: 'paquete_danado', label: 'Paquete dañado' },
            { id: 'rechazado', label: 'Rechazado por cliente' },
            { id: 'zona_peligrosa', label: 'Zona peligrosa/inaccesible' },
            { id: 'otro', label: 'Otro' }
        ]
    }
});

// Estado reactivo
const mensajeroSeleccionado = ref(null);
const showModalDevolucion = ref(false);
const paqueteSeleccionado = ref(null);
const motivoDevolucion = ref('');
const notasDevolucion = ref('');
const filtroEstado = ref('todos');

// Computed
const mensajerosFiltrados = computed(() => {
    if (filtroEstado.value === 'todos') return props.mensajeros;
    return props.mensajeros.filter(m => m.estado === filtroEstado.value);
});

const paquetesPendientesCount = computed(() => {
    return props.paquetesPendientes.filter(p => !p.motivo).length;
});

const paquetesDevueltosCount = computed(() => {
    return props.paquetesPendientes.filter(p => p.motivo).length;
});

const progresoConciliacion = computed(() => {
    const total = props.mensajeros.length;
    const liquidados = props.mensajeros.filter(m => m.estado === 'liquidado').length;
    return Math.round((liquidados / total) * 100);
});

// Métodos
const seleccionarMensajero = (mensajero) => {
    mensajeroSeleccionado.value = mensajero;
};

const abrirModalDevolucion = (paquete) => {
    paqueteSeleccionado.value = paquete;
    motivoDevolucion.value = '';
    notasDevolucion.value = '';
    showModalDevolucion.value = true;
};

const cerrarModalDevolucion = () => {
    showModalDevolucion.value = false;
    paqueteSeleccionado.value = null;
    motivoDevolucion.value = '';
    notasDevolucion.value = '';
};

const registrarDevolucion = () => {
    // Aquí iría la lógica para registrar la devolución
    console.log('Registrando devolución:', {
        paquete: paqueteSeleccionado.value,
        motivo: motivoDevolucion.value,
        notas: notasDevolucion.value
    });
    cerrarModalDevolucion();
};

const liquidarMensajero = (mensajero) => {
    // Aquí iría la lógica para liquidar al mensajero
    console.log('Liquidando mensajero:', mensajero);
};

// Icon components
function CalendarIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
        </svg>
    );
}

function PackageIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
    );
}

function CheckCircleIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );
}

function ClockIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );
}

function ArrowUturnLeftIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
    );
}

function UserIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
    );
}

function ClipboardDocumentCheckIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-9.75-10.5h9.75m-9.75 10.5h9.75m-15.5-7.5h1.5m-1.5 0v1.5m0-1.5l9.75-9.75m0 0h-1.5m1.5 0v1.5" />
        </svg>
    );
}

function MagnifyingGlassIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
    );
}

function ExclamationCircleIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
    );
}

function MapPinIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
        </svg>
    );
}

function XMarkIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    );
}
</script>

<template>
    <Head title="Conciliación de Cierre" />

    <AdminLayout title="Conciliación de Cierre">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-[var(--maya-text-main)] sm:truncate sm:text-3xl sm:tracking-tight">
                        Conciliación de Cierre
                    </h2>
                    <p class="mt-1 text-sm text-[var(--maya-text-muted)] flex items-center gap-2">
                        <CalendarIcon class="w-4 h-4" />
                        Fecha: {{ conciliacion.fecha }}
                    </p>
                </div>
                <div class="mt-4 flex md:ml-4 md:mt-0">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md bg-[var(--maya-primary)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--maya-primary)] transition-all duration-200"
                    >
                        <ClipboardDocumentCheckIcon class="w-4 h-4 mr-2" />
                        Finalizar Cierre del Día
                    </button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-[var(--maya-text-main)]">Progreso de Conciliación</span>
                    <span class="text-sm font-medium text-[var(--maya-primary)]">{{ progresoConciliacion }}%</span>
                </div>
                <div class="w-full bg-[var(--maya-border)] rounded-full h-2.5">
                    <div
                        class="bg-[var(--maya-primary)] h-2.5 rounded-full transition-all duration-500"
                        :style="{ width: `${progresoConciliacion}%` }"
                    ></div>
                </div>
                <p class="mt-2 text-xs text-[var(--maya-text-muted)]">
                    {{ mensajeros.filter(m => m.estado === 'liquidado').length }} de {{ mensajeros.length }} mensajeros han liquidado su carga
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Paquetes -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-[var(--maya-primary)] p-3">
                            <PackageIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Total Paquetes</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-text-main)]">{{ conciliacion.total_paquetes }}</p>
                    </dd>
                </div>

                <!-- Entregados -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-[var(--maya-success)] p-3">
                            <CheckCircleIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Entregados</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-success)]">{{ conciliacion.entregados }}</p>
                        <span class="ml-2 text-sm text-[var(--maya-text-muted)]">
                            {{ Math.round((conciliacion.entregados / conciliacion.total_paquetes) * 100) }}%
                        </span>
                    </dd>
                </div>

                <!-- Pendientes -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-[var(--maya-warning)] p-3">
                            <ClockIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Pendientes</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-warning)]">{{ conciliacion.pendientes }}</p>
                    </dd>
                </div>

                <!-- Devueltos -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-[var(--maya-danger)] p-3">
                            <ArrowUturnLeftIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Devueltos</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-danger)]">{{ conciliacion.devueltos }}</p>
                    </dd>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Lista de Mensajeros -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                        <div class="p-4 border-b border-[var(--maya-border)]">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-[var(--maya-text-main)]">Mensajeros</h3>
                                <select
                                    v-model="filtroEstado"
                                    class="text-sm border border-[var(--maya-border)] rounded-md bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)] hover:border-[var(--maya-primary)] transition-colors cursor-pointer"
                                >
                                    <option value="todos">Todos</option>
                                    <option value="pendiente">Pendientes</option>
                                    <option value="liquidado">Liquidados</option>
                                </select>
                            </div>
                        </div>
                        <div class="divide-y divide-[var(--maya-border)] max-h-[500px] overflow-y-auto">
                            <button
                                v-for="mensajero in mensajerosFiltrados"
                                :key="mensajero.id"
                                @click="seleccionarMensajero(mensajero)"
                                :class="[
                                    'w-full p-4 text-left hover:bg-[var(--maya-hover-surface)] transition-colors',
                                    mensajeroSeleccionado?.id === mensajero.id ? 'bg-[var(--maya-primary-alpha)]' : ''
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div :class="[
                                            'w-10 h-10 rounded-full flex items-center justify-center mr-3',
                                            mensajero.estado === 'liquidado' ? 'bg-[var(--maya-success-alpha)]' : 'bg-[var(--maya-warning-alpha)]'
                                        ]">
                                            <UserIcon :class="[
                                                'w-5 h-5',
                                                mensajero.estado === 'liquidado' ? 'text-[var(--maya-success)]' : 'text-[var(--maya-warning)]'
                                            ]" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-[var(--maya-text-main)]">{{ mensajero.nombre }}</p>
                                            <p class="text-xs text-[var(--maya-text-muted)]">
                                                {{ mensajero.entregados }}/{{ mensajero.total_asignados }} entregados
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span :class="[
                                            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                            mensajero.estado === 'liquidado'
                                                ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success)]'
                                                : 'bg-[var(--maya-warning-alpha)] text-[var(--maya-warning)]'
                                        ]">
                                            {{ mensajero.estado === 'liquidado' ? 'Liquidado' : 'Pendiente' }}
                                        </span>
                                        <p class="mt-1 text-xs text-[var(--maya-text-muted)]">
                                            {{ mensajero.pendientes }} pend / {{ mensajero.devueltos }} dev
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Detalle del Mensajero Seleccionado -->
                <div class="lg:col-span-2">
                    <div v-if="mensajeroSeleccionado" class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                        <div class="p-4 border-b border-[var(--maya-border)]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-[var(--maya-text-main)]">
                                        {{ mensajeroSeleccionado.nombre }}
                                    </h3>
                                    <p class="text-sm text-[var(--maya-text-muted)]">
                                        Liquidación de carga física vs digital
                                    </p>
                                </div>
                                <button
                                    v-if="mensajeroSeleccionado.estado === 'pendiente'"
                                    @click="liquidarMensajero(mensajeroSeleccionado)"
                                    class="inline-flex items-center rounded-md bg-[var(--maya-success)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-110 transition-all duration-200"
                                >
                                    <CheckCircleIcon class="w-4 h-4 mr-2" />
                                    Liquidar Carga
                                </button>
                            </div>
                        </div>

                        <!-- Resumen del Mensajero -->
                        <div class="p-4 grid grid-cols-4 gap-4 border-b border-[var(--maya-border)]">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-[var(--maya-text-main)]">{{ mensajeroSeleccionado.total_asignados }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Asignados</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-[var(--maya-success)]">{{ mensajeroSeleccionado.entregados }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Entregados</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-[var(--maya-warning)]">{{ mensajeroSeleccionado.pendientes }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Pendientes</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-[var(--maya-danger)]">{{ mensajeroSeleccionado.devueltos }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Devueltos</p>
                            </div>
                        </div>

                        <!-- Paquetes Pendientes -->
                        <div class="p-4">
                            <h4 class="text-sm font-medium text-[var(--maya-text-main)] mb-3 flex items-center">
                                <ExclamationCircleIcon class="w-4 h-4 mr-2 text-[var(--maya-warning)]" />
                                Paquetes Pendientes de Conciliar
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[var(--maya-border)]">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase">Tracking</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase">Cliente</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase">Dirección</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase">Estado</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-[var(--maya-text-muted)] uppercase">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--maya-border)]">
                                        <tr v-for="paquete in paquetesPendientes" :key="paquete.id">
                                            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-[var(--maya-primary)]">
                                                {{ paquete.tracking }}
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-[var(--maya-text-main)]">
                                                {{ paquete.cliente }}
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-[var(--maya-text-muted)]">
                                                <div class="flex items-center">
                                                    <MapPinIcon class="w-3 h-3 mr-1" />
                                                    {{ paquete.direccion }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <span
                                                    v-if="paquete.motivo"
                                                    class="inline-flex items-center rounded-full bg-[var(--maya-danger-alpha)] px-2 py-1 text-xs font-medium text-[var(--maya-danger)]"
                                                >
                                                    Devuelto: {{ paquete.motivo }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center rounded-full bg-[var(--maya-warning-alpha)] px-2 py-1 text-xs font-medium text-[var(--maya-warning)]"
                                                >
                                                    Pendiente
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap text-right text-sm">
                                                <button
                                                    v-if="!paquete.motivo"
                                                    @click="abrirModalDevolucion(paquete)"
                                                    class="text-[var(--maya-primary)] hover:text-[var(--maya-primary-dark)] font-medium"
                                                >
                                                    Registrar Devolución
                                                </button>
                                                <span v-else class="text-[var(--maya-text-muted)]">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay mensajero seleccionado -->
                    <div v-else class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm p-8 text-center">
                        <MagnifyingGlassIcon class="w-12 h-12 mx-auto text-[var(--maya-text-muted)] mb-4" />
                        <h3 class="text-lg font-medium text-[var(--maya-text-main)] mb-2">Selecciona un mensajero</h3>
                        <p class="text-sm text-[var(--maya-text-muted)]">
                            Haz clic en un mensajero de la lista para ver su detalle y conciliar su carga.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sección de Paquetes Devueltos para Re-ingreso -->
            <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                <div class="p-4 border-b border-[var(--maya-border)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-[var(--maya-text-main)]">Paquetes Devueltos - Re-ingreso al Almacén</h3>
                            <p class="text-sm text-[var(--maya-text-muted)]">
                                Paquetes que deben ser re-ingresados al almacén para entrega del día siguiente
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-[var(--maya-danger-alpha)] px-3 py-1 text-sm font-medium text-[var(--maya-danger)]">
                            {{ paquetesDevueltosCount }} paquetes
                        </span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="paquete in paquetesPendientes.filter(p => p.motivo)"
                            :key="paquete.id"
                            class="border border-[var(--maya-border)] rounded-lg p-4 bg-[var(--maya-bg-base)]"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium text-[var(--maya-primary)]">{{ paquete.tracking }}</p>
                                    <p class="text-sm text-[var(--maya-text-main)]">{{ paquete.cliente }}</p>
                                    <p class="text-xs text-[var(--maya-text-muted)] flex items-center mt-1">
                                        <MapPinIcon class="w-3 h-3 mr-1" />
                                        {{ paquete.direccion }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-[var(--maya-danger-alpha)] px-2 py-1 text-xs font-medium text-[var(--maya-danger)]">
                                    Devuelto
                                </span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-[var(--maya-border)]">
                                <p class="text-xs text-[var(--maya-text-muted)]">Motivo:</p>
                                <p class="text-sm text-[var(--maya-text-main)]">{{ paquete.motivo }}</p>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button
                                    class="flex-1 inline-flex justify-center items-center rounded-md bg-[var(--maya-primary)] px-3 py-2 text-xs font-medium text-white hover:brightness-110 transition-all duration-200"
                                >
                                    Re-ingresar
                                </button>
                                <button
                                    class="flex-1 inline-flex justify-center items-center rounded-md bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-3 py-2 text-xs font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] hover:border-[var(--maya-primary)] transition-all duration-200"
                                >
                                    Reprogramar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Registrar Devolución -->
        <Modal :show="showModalDevolucion" @close="cerrarModalDevolucion">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-[var(--maya-text-main)]">
                        Registrar Devolución
                    </h3>
                    <button
                        @click="cerrarModalDevolucion"
                        class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="paqueteSeleccionado" class="space-y-4">
                    <div class="bg-[var(--maya-bg-base)] rounded-md p-4 border border-[var(--maya-border)]">
                        <p class="text-sm text-[var(--maya-text-muted)]">Paquete</p>
                        <p class="text-lg font-medium text-[var(--maya-primary)]">{{ paqueteSeleccionado.tracking }}</p>
                        <p class="text-sm text-[var(--maya-text-main)]">{{ paqueteSeleccionado.cliente }}</p>
                        <p class="text-xs text-[var(--maya-text-muted)] flex items-center mt-1">
                            <MapPinIcon class="w-3 h-3 mr-1" />
                            {{ paqueteSeleccionado.direccion }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--maya-text-main)] mb-2">
                            Motivo de No Entrega <span class="text-[var(--maya-danger)]">*</span>
                        </label>
                        <select
                            v-model="motivoDevolucion"
                            class="w-full border border-[var(--maya-border)] rounded-md bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)] hover:border-[var(--maya-primary)] transition-colors cursor-pointer"
                        >
                            <option value="">Selecciona un motivo</option>
                            <option
                                v-for="motivo in motivosDevolucion"
                                :key="motivo.id"
                                :value="motivo.id"
                            >
                                {{ motivo.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--maya-text-main)] mb-2">
                            Notas Adicionales
                        </label>
                        <textarea
                            v-model="notasDevolucion"
                            rows="3"
                            class="w-full border border-[var(--maya-border)] rounded-md bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)] hover:border-[var(--maya-primary)] transition-colors"
                            placeholder="Detalles adicionales sobre la devolución..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-[var(--maya-border)]">
                        <button
                            @click="cerrarModalDevolucion"
                            class="inline-flex items-center rounded-md bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 py-2 text-sm font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] hover:border-[var(--maya-primary)] transition-all duration-200"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="registrarDevolucion"
                            :disabled="!motivoDevolucion"
                            class="inline-flex items-center rounded-md bg-[var(--maya-danger)] px-4 py-2 text-sm font-medium text-white hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                        >
                            <ArrowUturnLeftIcon class="w-4 h-4 mr-2" />
                            Confirmar Devolución
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
