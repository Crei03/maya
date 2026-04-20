<script setup lang="jsx">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

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
const vistaActual = ref('resumen'); // 'resumen', 'mensajeros', 'paquetes', 'devolucion'
const mensajeroSeleccionado = ref(null);
const paqueteSeleccionado = ref(null);
const motivoDevolucion = ref('');
const notasDevolucion = ref('');
const mostrarToast = ref(false);
const mensajeToast = ref('');

// Computed
const mensajerosPendientes = computed(() => {
    return props.mensajeros.filter(m => m.estado === 'pendiente');
});

const mensajerosLiquidados = computed(() => {
    return props.mensajeros.filter(m => m.estado === 'liquidado');
});

const paquetesPendientesCount = computed(() => {
    return props.paquetesPendientes.filter(p => !p.motivo).length;
});

const paquetesDevueltos = computed(() => {
    return props.paquetesPendientes.filter(p => p.motivo);
});

const progresoConciliacion = computed(() => {
    const total = props.mensajeros.length;
    const liquidados = props.mensajeros.filter(m => m.estado === 'liquidado').length;
    return Math.round((liquidados / total) * 100);
});

// Métodos
const cambiarVista = (vista) => {
    vistaActual.value = vista;
    if (vista !== 'devolucion') {
        paqueteSeleccionado.value = null;
        motivoDevolucion.value = '';
        notasDevolucion.value = '';
    }
};

const seleccionarMensajero = (mensajero) => {
    mensajeroSeleccionado.value = mensajero;
    cambiarVista('paquetes');
};

const abrirDevolucion = (paquete) => {
    paqueteSeleccionado.value = paquete;
    motivoDevolucion.value = '';
    notasDevolucion.value = '';
    cambiarVista('devolucion');
};

const registrarDevolucion = () => {
    // Aquí iría la lógica para registrar la devolución
    mostrarToast.value = true;
    mensajeToast.value = 'Devolución registrada exitosamente';
    setTimeout(() => {
        mostrarToast.value = false;
    }, 3000);
    cambiarVista('paquetes');
};

const liquidarMensajero = (mensajero) => {
    mostrarToast.value = true;
    mensajeToast.value = `${mensajero.nombre} liquidado exitosamente`;
    setTimeout(() => {
        mostrarToast.value = false;
    }, 3000);
    cambiarVista('mensajeros');
};

const finalizarCierre = () => {
    mostrarToast.value = true;
    mensajeToast.value = 'Cierre del día finalizado';
    setTimeout(() => {
        mostrarToast.value = false;
    }, 3000);
};

// Icon components
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

function PackageIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
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

function ChevronLeftIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
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

function ChartPieIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
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

function InboxIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z" />
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
</script>

<template>
    <Head title="Conciliación de Cierre" />

    <AdminLayout title="Conciliación">
        <div class="min-h-screen pb-20">
            <!-- Toast Notification -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-2 opacity-0"
            >
                <div
                    v-if="mostrarToast"
                    class="fixed top-4 left-4 right-4 z-50 bg-[var(--maya-success)] text-white px-4 py-3 rounded-lg shadow-lg flex items-center justify-center"
                >
                    <CheckCircleIcon class="w-5 h-5 mr-2" />
                    {{ mensajeToast }}
                </div>
            </Transition>

            <!-- VISTA: RESUMEN -->
            <div v-if="vistaActual === 'resumen'" class="space-y-4 p-4">
                <!-- Header -->
                <div class="text-center py-4">
                    <h1 class="text-xl font-bold text-[var(--maya-text-main)]">Conciliación de Cierre</h1>
                    <p class="text-sm text-[var(--maya-text-muted)]">{{ conciliacion.fecha }}</p>
                </div>

                <!-- Progress Card -->
                <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-[var(--maya-text-main)]">Progreso</span>
                        <span class="text-lg font-bold text-[var(--maya-primary)]">{{ progresoConciliacion }}%</span>
                    </div>
                    <div class="w-full bg-[var(--maya-border)] rounded-full h-3">
                        <div
                            class="bg-[var(--maya-primary)] h-3 rounded-full transition-all duration-500"
                            :style="{ width: `${progresoConciliacion}%` }"
                        ></div>
                    </div>
                    <p class="mt-2 text-xs text-[var(--maya-text-muted)] text-center">
                        {{ mensajeros.filter(m => m.estado === 'liquidado').length }} de {{ mensajeros.length }} mensajeros
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-[var(--maya-primary-alpha)] flex items-center justify-center">
                            <PackageIcon class="w-6 h-6 text-[var(--maya-primary)]" />
                        </div>
                        <p class="text-2xl font-bold text-[var(--maya-text-main)]">{{ conciliacion.total_paquetes }}</p>
                        <p class="text-xs text-[var(--maya-text-muted)]">Total</p>
                    </div>
                    <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-[var(--maya-success-alpha)] flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-[var(--maya-success)]" />
                        </div>
                        <p class="text-2xl font-bold text-[var(--maya-success)]">{{ conciliacion.entregados }}</p>
                        <p class="text-xs text-[var(--maya-text-muted)]">Entregados</p>
                    </div>
                    <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-[var(--maya-warning-alpha)] flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-[var(--maya-warning)]" />
                        </div>
                        <p class="text-2xl font-bold text-[var(--maya-warning)]">{{ conciliacion.pendientes }}</p>
                        <p class="text-xs text-[var(--maya-text-muted)]">Pendientes</p>
                    </div>
                    <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-[var(--maya-danger-alpha)] flex items-center justify-center">
                            <ArrowUturnLeftIcon class="w-6 h-6 text-[var(--maya-danger)]" />
                        </div>
                        <p class="text-2xl font-bold text-[var(--maya-danger)]">{{ conciliacion.devueltos }}</p>
                        <p class="text-xs text-[var(--maya-text-muted)]">Devueltos</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3 pt-4">
                    <button
                        @click="cambiarVista('mensajeros')"
                        class="w-full flex items-center justify-between bg-[var(--maya-primary)] text-white p-4 rounded-lg min-h-[56px] active:brightness-110 transition-all duration-200"
                    >
                        <div class="flex items-center">
                            <UsersIcon class="w-6 h-6 mr-3" />
                            <span class="font-medium">Ver Mensajeros</span>
                        </div>
                        <span class="bg-white/20 px-2 py-1 rounded text-sm">
                            {{ mensajerosPendientes.length }} pend
                        </span>
                    </button>

                    <button
                        @click="cambiarVista('devueltos')"
                        class="w-full flex items-center justify-between bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-[var(--maya-text-main)] p-4 rounded-lg min-h-[56px] active:bg-[var(--maya-hover-surface)] active:border-[var(--maya-primary)] transition-all duration-200"
                    >
                        <div class="flex items-center">
                            <InboxIcon class="w-6 h-6 mr-3 text-[var(--maya-danger)]" />
                            <span class="font-medium">Paquetes Devueltos</span>
                        </div>
                        <span class="bg-[var(--maya-danger-alpha)] text-[var(--maya-danger)] px-2 py-1 rounded text-sm">
                            {{ paquetesDevueltos.length }}
                        </span>
                    </button>
                </div>

                <!-- Finalizar Button -->
                <button
                    @click="finalizarCierre"
                    class="w-full bg-[var(--maya-success)] text-white p-4 rounded-lg font-semibold min-h-[56px] flex items-center justify-center mt-6 active:brightness-110 transition-all duration-200"
                >
                    <ClipboardDocumentCheckIcon class="w-5 h-5 mr-2" />
                    Finalizar Cierre del Día
                </button>
            </div>

            <!-- VISTA: MENSAJEROS -->
            <div v-if="vistaActual === 'mensajeros'" class="space-y-4">
                <!-- Header -->
                <div class="sticky top-0 bg-[var(--maya-bg-base)] z-10 p-4 border-b border-[var(--maya-border)]">
                    <div class="flex items-center">
                        <button
                            @click="cambiarVista('resumen')"
                            class="p-2 -ml-2 mr-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        >
                            <ChevronLeftIcon class="w-6 h-6 text-[var(--maya-text-main)]" />
                        </button>
                        <h2 class="text-lg font-semibold text-[var(--maya-text-main)]">Mensajeros</h2>
                    </div>
                </div>

                <div class="px-4 space-y-3">
                    <!-- Mensajeros Pendientes -->
                    <div v-if="mensajerosPendientes.length > 0">
                        <h3 class="text-sm font-medium text-[var(--maya-text-muted)] mb-2 px-1">Pendientes de Liquidar</h3>
                        <div class="space-y-2">
                            <button
                                v-for="mensajero in mensajerosPendientes"
                                :key="mensajero.id"
                                @click="seleccionarMensajero(mensajero)"
                                class="w-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 text-left min-h-[72px] active:bg-[var(--maya-hover-surface)] active:border-[var(--maya-primary)] transition-all duration-200"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 rounded-full bg-[var(--maya-warning-alpha)] flex items-center justify-center mr-3">
                                            <UserIcon class="w-6 h-6 text-[var(--maya-warning)]" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-[var(--maya-text-main)]">{{ mensajero.nombre }}</p>
                                            <p class="text-sm text-[var(--maya-text-muted)]">
                                                {{ mensajero.entregados }}/{{ mensajero.total_asignados }} entregados
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center rounded-full bg-[var(--maya-warning-alpha)] px-2 py-1 text-xs font-medium text-[var(--maya-warning)]">
                                            {{ mensajero.pendientes }} pend
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Mensajeros Liquidados -->
                    <div v-if="mensajerosLiquidados.length > 0" class="pt-4">
                        <h3 class="text-sm font-medium text-[var(--maya-text-muted)] mb-2 px-1">Liquidados</h3>
                        <div class="space-y-2">
                            <div
                                v-for="mensajero in mensajerosLiquidados"
                                :key="mensajero.id"
                                class="w-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 opacity-75"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 rounded-full bg-[var(--maya-success-alpha)] flex items-center justify-center mr-3">
                                            <CheckCircleIcon class="w-6 h-6 text-[var(--maya-success)]" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-[var(--maya-text-main)]">{{ mensajero.nombre }}</p>
                                            <p class="text-sm text-[var(--maya-success)]">Liquidado</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISTA: PAQUETES (de un mensajero) -->
            <div v-if="vistaActual === 'paquetes' && mensajeroSeleccionado" class="space-y-4">
                <!-- Header -->
                <div class="sticky top-0 bg-[var(--maya-bg-base)] z-10 p-4 border-b border-[var(--maya-border)]">
                    <div class="flex items-center">
                        <button
                            @click="cambiarVista('mensajeros')"
                            class="p-2 -ml-2 mr-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        >
                            <ChevronLeftIcon class="w-6 h-6 text-[var(--maya-text-main)]" />
                        </button>
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--maya-text-main)]">{{ mensajeroSeleccionado.nombre }}</h2>
                            <p class="text-sm text-[var(--maya-text-muted)]">
                                {{ mensajeroSeleccionado.entregados }}/{{ mensajeroSeleccionado.total_asignados }} entregados
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resumen del Mensajero -->
                <div class="px-4">
                    <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-xl font-bold text-[var(--maya-success)]">{{ mensajeroSeleccionado.entregados }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Entregados</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-[var(--maya-warning)]">{{ mensajeroSeleccionado.pendientes }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Pendientes</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-[var(--maya-danger)]">{{ mensajeroSeleccionado.devueltos }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Devueltos</p>
                        </div>
                    </div>
                </div>

                <!-- Lista de Paquetes Pendientes -->
                <div class="px-4">
                    <h3 class="text-sm font-medium text-[var(--maya-text-muted)] mb-2">Paquetes Pendientes</h3>
                    <div class="space-y-2">
                        <div
                            v-for="paquete in paquetesPendientes.filter(p => !p.motivo)"
                            :key="paquete.id"
                            class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-[var(--maya-primary)]">{{ paquete.tracking }}</p>
                                    <p class="text-sm text-[var(--maya-text-main)]">{{ paquete.cliente }}</p>
                                    <p class="text-xs text-[var(--maya-text-muted)] flex items-center mt-1">
                                        <MapPinIcon class="w-3 h-3 mr-1" />
                                        {{ paquete.direccion }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="abrirDevolucion(paquete)"
                                class="w-full mt-3 bg-[var(--maya-danger)] text-white py-3 rounded-lg text-sm font-medium min-h-[48px] active:brightness-110 transition-all duration-200"
                            >
                                Registrar Devolución
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Liquidar Button -->
                <div class="px-4 pt-4 pb-8">
                    <button
                        @click="liquidarMensajero(mensajeroSeleccionado)"
                        class="w-full bg-[var(--maya-success)] text-white p-4 rounded-lg font-semibold min-h-[56px] flex items-center justify-center active:brightness-110 transition-all duration-200"
                    >
                        <CheckCircleIcon class="w-5 h-5 mr-2" />
                        Liquidar Mensajero
                    </button>
                </div>
            </div>

            <!-- VISTA: FORMULARIO DEVOLUCIÓN -->
            <div v-if="vistaActual === 'devolucion' && paqueteSeleccionado" class="space-y-4">
                <!-- Header -->
                <div class="sticky top-0 bg-[var(--maya-bg-base)] z-10 p-4 border-b border-[var(--maya-border)]">
                    <div class="flex items-center">
                        <button
                            @click="cambiarVista('paquetes')"
                            class="p-2 -ml-2 mr-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        >
                            <ChevronLeftIcon class="w-6 h-6 text-[var(--maya-text-main)]" />
                        </button>
                        <h2 class="text-lg font-semibold text-[var(--maya-text-main)]">Registrar Devolución</h2>
                    </div>
                </div>

                <div class="px-4 space-y-4">
                    <!-- Info del Paquete -->
                    <div class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4">
                        <p class="text-sm text-[var(--maya-text-muted)] mb-1">Paquete</p>
                        <p class="text-lg font-bold text-[var(--maya-primary)]">{{ paqueteSeleccionado.tracking }}</p>
                        <p class="text-base text-[var(--maya-text-main)] mt-1">{{ paqueteSeleccionado.cliente }}</p>
                        <p class="text-sm text-[var(--maya-text-muted)] flex items-center mt-1">
                            <MapPinIcon class="w-4 h-4 mr-1" />
                            {{ paqueteSeleccionado.direccion }}
                        </p>
                    </div>

                    <!-- Motivo -->
                    <div>
                        <label class="block text-base font-medium text-[var(--maya-text-main)] mb-2">
                            Motivo de No Entrega <span class="text-[var(--maya-danger)]">*</span>
                        </label>
                        <div class="space-y-2">
                            <button
                                v-for="motivo in motivosDevolucion"
                                :key="motivo.id"
                                @click="motivoDevolucion = motivo.id"
                                :class="[
                                    'w-full p-4 rounded-lg text-left border min-h-[56px] active:brightness-95 transition-all duration-200',
                                    motivoDevolucion === motivo.id
                                        ? 'bg-[var(--maya-primary-alpha)] border-[var(--maya-primary)]'
                                        : 'bg-[var(--maya-bg-surface)] border-[var(--maya-border)] active:bg-[var(--maya-hover-surface)]'
                                ]"
                            >
                                <div class="flex items-center">
                                    <div :class="[
                                        'w-5 h-5 rounded-full border-2 mr-3 flex items-center justify-center',
                                        motivoDevolucion === motivo.id
                                            ? 'border-[var(--maya-primary)]'
                                            : 'border-[var(--maya-border)]'
                                    ]">
                                        <div
                                            v-if="motivoDevolucion === motivo.id"
                                            class="w-3 h-3 rounded-full bg-[var(--maya-primary)]"
                                        ></div>
                                    </div>
                                    <span :class="[
                                        'font-medium',
                                        motivoDevolucion === motivo.id ? 'text-[var(--maya-primary)]' : 'text-[var(--maya-text-main)]'
                                    ]">
                                        {{ motivo.label }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div>
                        <label class="block text-base font-medium text-[var(--maya-text-main)] mb-2">
                            Notas Adicionales
                        </label>
                        <textarea
                            v-model="notasDevolucion"
                            rows="4"
                            class="w-full border border-[var(--maya-border)] rounded-lg bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] p-4 text-base focus:ring-2 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)] active:border-[var(--maya-primary)] transition-colors"
                            placeholder="Detalles adicionales sobre la devolución..."
                        ></textarea>
                    </div>
                </div>

                <!-- Confirmar Button -->
                <div class="px-4 pt-4 pb-8">
                    <button
                        @click="registrarDevolucion"
                        :disabled="!motivoDevolucion"
                        class="w-full bg-[var(--maya-danger)] text-white p-4 rounded-lg font-semibold min-h-[56px] flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed active:brightness-110 transition-all duration-200"
                    >
                        <ArrowUturnLeftIcon class="w-5 h-5 mr-2" />
                        Confirmar Devolución
                    </button>
                </div>
            </div>

            <!-- VISTA: PAQUETES DEVUELTOS -->
            <div v-if="vistaActual === 'devueltos'" class="space-y-4">
                <!-- Header -->
                <div class="sticky top-0 bg-[var(--maya-bg-base)] z-10 p-4 border-b border-[var(--maya-border)]">
                    <div class="flex items-center">
                        <button
                            @click="cambiarVista('resumen')"
                            class="p-2 -ml-2 mr-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        >
                            <ChevronLeftIcon class="w-6 h-6 text-[var(--maya-text-main)]" />
                        </button>
                        <h2 class="text-lg font-semibold text-[var(--maya-text-main)]">Paquetes Devueltos</h2>
                    </div>
                </div>

                <div class="px-4 space-y-3">
                    <p class="text-sm text-[var(--maya-text-muted)]">
                        Paquetes para re-ingresar al almacén y reprogramar entrega
                    </p>

                    <div
                        v-for="paquete in paquetesDevueltos"
                        :key="paquete.id"
                        class="bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] rounded-lg p-4"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-[var(--maya-primary)]">{{ paquete.tracking }}</p>
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
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button
                                class="bg-[var(--maya-primary)] text-white py-3 rounded-lg text-sm font-medium min-h-[48px] active:brightness-110 transition-all duration-200"
                            >
                                Re-ingresar
                            </button>
                            <button
                                class="bg-[var(--maya-bg-base)] border border-[var(--maya-border)] text-[var(--maya-text-main)] py-3 rounded-lg text-sm font-medium min-h-[48px] active:bg-[var(--maya-hover-surface)] active:border-[var(--maya-primary)] transition-all duration-200"
                            >
                                Reprogramar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
