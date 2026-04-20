<script setup lang="jsx">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// Props - datos del servidor
const props = defineProps({
    paquetesDisponibles: {
        type: Array,
        default: () => [
            { id: 'ship-001', tracking_number: 'MAYA001234', recipient_name: 'Juan Martínez', destination_address: 'Calle 50 #12-34, Ciudad de Panamá', weight_kg: 2.5, total_cost: 15.00 },
            { id: 'ship-002', tracking_number: 'MAYA001235', recipient_name: 'María López', destination_address: 'Av. Balboa #45, Ciudad de Panamá', weight_kg: 1.8, total_cost: 12.50 },
            { id: 'ship-003', tracking_number: 'MAYA001236', recipient_name: 'Pedro Sánchez', destination_address: 'Carrera 7 #89-12, San Miguelito', weight_kg: 3.2, total_cost: 18.00 },
            { id: 'ship-004', tracking_number: 'MAYA001237', recipient_name: 'Laura Torres', destination_address: 'Calle 100 #23-45, Ciudad de Panamá', weight_kg: 0.9, total_cost: 8.00 },
            { id: 'ship-005', tracking_number: 'MAYA001238', recipient_name: 'Carlos Ruiz', destination_address: 'Vía España #567, Ciudad de Panamá', weight_kg: 4.0, total_cost: 22.00 },
            { id: 'ship-006', tracking_number: 'MAYA001239', recipient_name: 'Ana Morales', destination_address: 'Calle 12 #8-90, Colón', weight_kg: 2.1, total_cost: 25.00 },
        ]
    },
    mensajeros: {
        type: Array,
        default: () => [
            { id: 'msg-001', full_name: 'Carlos Méndez', phone: '6123-4567', vehicle_id: 'MOTO-001', activo: true, paquetes_asignados_hoy: 12 },
            { id: 'msg-002', full_name: 'María González', phone: '6234-5678', vehicle_id: 'MOTO-002', activo: true, paquetes_asignados_hoy: 8 },
            { id: 'msg-003', full_name: 'Juan Pérez', phone: '6345-6789', vehicle_id: 'CAR-001', activo: true, paquetes_asignados_hoy: 15 },
            { id: 'msg-004', full_name: 'Ana Rodríguez', phone: '6456-7890', vehicle_id: 'MOTO-003', activo: false, paquetes_asignados_hoy: 0 },
        ]
    },
    manifiestosHoy: {
        type: Array,
        default: () => [
            { id: 'man-001', messenger: { full_name: 'Carlos Méndez' }, total_items: 12, status: 'En ruta', created_at: '2026-03-13 08:30:00' },
            { id: 'man-002', messenger: { full_name: 'María González' }, total_items: 8, status: 'Preparando', created_at: '2026-03-13 09:15:00' },
        ]
    }
});

// Estado reactivo
const vistaActual = ref('inicio'); // 'inicio', 'mensajero', 'paquetes', 'confirmar', 'manifiestos', 'detalle'
const mensajeroSeleccionado = ref(null);
const paquetesSeleccionados = ref([]);
const busquedaTracking = ref('');
const manifiestoDetalle = ref(null);
const isProcesando = ref(false);
const showToast = ref(false);
const mensajeToast = ref('');
const toastType = ref('success'); // 'success' | 'error'

// Computed
const mensajerosActivos = computed(() => {
    return props.mensajeros.filter(m => m.activo);
});

const paquetesFiltrados = computed(() => {
    if (!busquedaTracking.value) return props.paquetesDisponibles;
    const search = busquedaTracking.value.toLowerCase();
    return props.paquetesDisponibles.filter(p =>
        p.tracking_number.toLowerCase().includes(search) ||
        p.recipient_name.toLowerCase().includes(search)
    );
});

const totalPaquetesSeleccionados = computed(() => {
    return paquetesSeleccionados.value.length;
});

const pesoTotal = computed(() => {
    return paquetesSeleccionados.value.reduce((total, id) => {
        const paquete = props.paquetesDisponibles.find(p => p.id === id);
        return total + (paquete?.weight_kg || 0);
    }, 0).toFixed(2);
});

const valorTotal = computed(() => {
    return paquetesSeleccionados.value.reduce((total, id) => {
        const paquete = props.paquetesDisponibles.find(p => p.id === id);
        return total + (paquete?.total_cost || 0);
    }, 0).toFixed(2);
});

const puedeContinuar = computed(() => {
    if (vistaActual.value === 'mensajero') return mensajeroSeleccionado.value !== null;
    if (vistaActual.value === 'paquetes') return paquetesSeleccionados.value.length > 0;
    return true;
});

// Métodos
const cambiarVista = (vista) => {
    vistaActual.value = vista;
};

const seleccionarMensajero = (mensajero) => {
    mensajeroSeleccionado.value = mensajero;
};

const togglePaquete = (paqueteId) => {
    const index = paquetesSeleccionados.value.indexOf(paqueteId);
    if (index > -1) {
        paquetesSeleccionados.value.splice(index, 1);
    } else {
        paquetesSeleccionados.value.push(paqueteId);
    }
};

const mostrarToast = (mensaje, tipo = 'success') => {
    mensajeToast.value = mensaje;
    toastType.value = tipo;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

const crearManifiesto = async () => {
    isProcesando.value = true;

    // Simular llamada a API
    await new Promise(resolve => setTimeout(resolve, 1500));

    const nuevoManifiesto = {
        id: 'man-' + Date.now(),
        messenger: mensajeroSeleccionado.value,
        total_items: paquetesSeleccionados.value.length,
        status: 'Preparando',
        created_at: new Date().toISOString()
    };

    props.manifiestosHoy.unshift(nuevoManifiesto);

    mostrarToast(`Manifiesto creado con ${paquetesSeleccionados.value.length} paquetes`, 'success');

    // Reset
    paquetesSeleccionados.value = [];
    mensajeroSeleccionado.value = null;
    isProcesando.value = false;
    cambiarVista('manifiestos');
};

const verDetalleManifiesto = (manifiesto) => {
    manifiestoDetalle.value = manifiesto;
    cambiarVista('detalle');
};

const iniciarDespacho = (manifiestoId) => {
    const idx = props.manifiestosHoy.findIndex(m => m.id === manifiestoId);
    if (idx > -1) {
        props.manifiestosHoy[idx].status = 'En ruta';
    }
    mostrarToast('Despacho iniciado correctamente', 'success');
    cambiarVista('manifiestos');
};

const getStatusColor = (status) => {
    const colors = {
        'Preparando': 'bg-yellow-500',
        'En ruta': 'bg-blue-500',
        'Completado': 'bg-green-500'
    };
    return colors[status] || 'bg-gray-500';
};

const getStatusBg = (status) => {
    const colors = {
        'Preparando': 'bg-yellow-100 text-yellow-800',
        'En ruta': 'bg-blue-100 text-blue-800',
        'Completado': 'bg-green-100 text-green-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

// Icon components
function TruckIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
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

function UserIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
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

function MapPinIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
        </svg>
    );
}

function PlusIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    );
}

function ArrowLeftIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    );
}

function ArrowRightIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
    );
}

function DocumentTextIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
    );
}

function EyeIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    );
}

function HomeIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
    );
}

function SearchIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
    );
}
</script>

<template>
    <Head title="Asignación de Transporte" />

    <AdminLayout title="Asignación">
        <div class="min-h-[calc(100vh-4rem)] pb-20">
            <!-- Vista: Inicio / Dashboard -->
            <div v-if="vistaActual === 'inicio'" class="space-y-4">
                <!-- Header -->
                <div class="px-4 pt-4">
                    <h1 class="text-xl font-bold text-[var(--maya-text-main)]">Asignación de Transporte</h1>
                    <p class="text-sm text-[var(--maya-text-muted)]">Gestione manifiestos y asignaciones</p>
                </div>

                <!-- Acciones Rápidas -->
                <div class="px-4 space-y-3">
                    <button
                        @click="cambiarVista('mensajero')"
                        class="w-full flex items-center gap-4 p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] active:scale-[0.98] transition-transform"
                    >
                        <div class="w-12 h-12 rounded-full bg-[var(--maya-primary)] flex items-center justify-center flex-shrink-0">
                            <PlusIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-semibold text-[var(--maya-text-main)]">Nuevo Manifiesto</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Asignar paquetes a mensajero</p>
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-[var(--maya-text-muted)]" />
                    </button>

                    <button
                        @click="cambiarVista('manifiestos')"
                        class="w-full flex items-center gap-4 p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] active:scale-[0.98] transition-transform"
                    >
                        <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">
                            <DocumentTextIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-semibold text-[var(--maya-text-main)]">Ver Manifiestos</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">{{ manifiestosHoy.length }} manifiestos hoy</p>
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-[var(--maya-text-muted)]" />
                    </button>
                </div>

                <!-- Resumen -->
                <div class="px-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-2xl font-bold text-[var(--maya-primary)]">{{ mensajerosActivos.length }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Mensajeros Activos</p>
                        </div>
                        <div class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-2xl font-bold text-[var(--maya-success)]">{{ paquetesDisponibles.length }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Paquetes Disponibles</p>
                        </div>
                    </div>
                </div>

                <!-- Manifiestos Recientes -->
                <div class="px-4" v-if="manifiestosHoy.length > 0">
                    <h2 class="text-sm font-semibold text-[var(--maya-text-main)] mb-3">Manifiestos Recientes</h2>
                    <div class="space-y-2">
                        <div
                            v-for="manifiesto in manifiestosHoy.slice(0, 3)"
                            :key="manifiesto.id"
                            @click="verDetalleManifiesto(manifiesto)"
                            class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] flex items-center gap-3"
                        >
                            <div class="w-10 h-10 rounded-full bg-[var(--maya-primary-alpha)] flex items-center justify-center flex-shrink-0">
                                <DocumentTextIcon class="w-5 h-5 text-[var(--maya-primary)]" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[var(--maya-text-main)] truncate">{{ manifiesto.messenger.full_name }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">{{ manifiesto.total_items }} paquetes</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span :class="['w-3 h-3 rounded-full', getStatusColor(manifiesto.status)]"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista: Seleccionar Mensajero -->
            <div v-if="vistaActual === 'mensajero'" class="space-y-4">
                <!-- Header con Back -->
                <div class="px-4 pt-4 flex items-center gap-3">
                    <button
                        @click="cambiarVista('inicio')"
                        class="w-10 h-10 rounded-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] flex items-center justify-center"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-[var(--maya-text-main)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Seleccionar Mensajero</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">Paso 1 de 3</p>
                    </div>
                </div>

                <!-- Lista de Mensajeros -->
                <div class="px-4 space-y-3">
                    <div
                        v-for="mensajero in mensajerosActivos"
                        :key="mensajero.id"
                        @click="seleccionarMensajero(mensajero)"
                        :class="[
                            'p-4 rounded-xl border-2 transition-all active:scale-[0.98]',
                            mensajeroSeleccionado?.id === mensajero.id
                                ? 'bg-[var(--maya-primary-alpha)] border-[var(--maya-primary)]'
                                : 'bg-[var(--maya-bg-surface)] border-[var(--maya-border)]'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-[var(--maya-primary)] flex items-center justify-center text-white font-semibold flex-shrink-0">
                                {{ mensajero.full_name.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-[var(--maya-text-main)]">{{ mensajero.full_name }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">{{ mensajero.vehicle_id }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">{{ mensajero.paquetes_asignados_hoy }} asignados hoy</p>
                            </div>
                            <div v-if="mensajeroSeleccionado?.id === mensajero.id">
                                <CheckCircleIcon class="w-6 h-6 text-[var(--maya-primary)]" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón Continuar -->
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--maya-bg-base)] border-t border-[var(--maya-border)]" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
                    <button
                        @click="cambiarVista('paquetes')"
                        :disabled="!mensajeroSeleccionado"
                        class="w-full py-3.5 rounded-xl font-semibold text-white transition-all active:scale-[0.98] disabled:opacity-50"
                        :class="mensajeroSeleccionado ? 'bg-[var(--maya-primary)]' : 'bg-gray-400'"
                    >
                        Continuar
                    </button>
                </div>
            </div>

            <!-- Vista: Seleccionar Paquetes -->
            <div v-if="vistaActual === 'paquetes'" class="space-y-4 pb-24">
                <!-- Header con Back -->
                <div class="px-4 pt-4 flex items-center gap-3">
                    <button
                        @click="cambiarVista('mensajero')"
                        class="w-10 h-10 rounded-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] flex items-center justify-center"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-[var(--maya-text-main)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Seleccionar Paquetes</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">Paso 2 de 3 • {{ mensajeroSeleccionado?.full_name }}</p>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="px-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <SearchIcon class="h-5 w-5 text-[var(--maya-text-muted)]" />
                        </div>
                        <input
                            v-model="busquedaTracking"
                            type="text"
                            inputmode="search"
                            placeholder="Buscar tracking..."
                            class="block w-full pl-10 pr-3 py-3 text-base border border-[var(--maya-border)] rounded-xl bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)]"
                        />
                    </div>
                </div>

                <!-- Contador -->
                <div class="px-4 flex items-center justify-between">
                    <span class="text-sm text-[var(--maya-text-muted)]">{{ paquetesFiltrados.length }} paquetes</span>
                    <span v-if="paquetesSeleccionados.length > 0" class="text-sm font-medium text-[var(--maya-primary)]">
                        {{ paquetesSeleccionados.length }} seleccionados
                    </span>
                </div>

                <!-- Lista de Paquetes -->
                <div class="px-4 space-y-2">
                    <div
                        v-for="paquete in paquetesFiltrados"
                        :key="paquete.id"
                        @click="togglePaquete(paquete.id)"
                        :class="[
                            'p-4 rounded-xl border-2 transition-all',
                            paquetesSeleccionados.includes(paquete.id)
                                ? 'bg-[var(--maya-primary-alpha)] border-[var(--maya-primary)]'
                                : 'bg-[var(--maya-bg-surface)] border-[var(--maya-border)]'
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5',
                                    paquetesSeleccionados.includes(paquete.id)
                                        ? 'bg-[var(--maya-primary)] border-[var(--maya-primary)]'
                                        : 'border-[var(--maya-border)]'
                                ]"
                            >
                                <CheckCircleIcon v-if="paquetesSeleccionados.includes(paquete.id)" class="w-4 h-4 text-white" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-sm font-bold text-[var(--maya-primary)]">{{ paquete.tracking_number }}</span>
                                    <span class="text-xs text-[var(--maya-text-muted)]">{{ paquete.weight_kg }}kg</span>
                                </div>
                                <p class="text-sm font-medium text-[var(--maya-text-main)]">{{ paquete.recipient_name }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)] flex items-center gap-1 mt-0.5">
                                    <MapPinIcon class="w-3 h-3" />
                                    {{ paquete.destination_address }}
                                </p>
                                <p class="text-sm font-semibold text-[var(--maya-text-main)] mt-2">${{ paquete.total_cost.toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="paquetesFiltrados.length === 0" class="px-4 py-12 text-center">
                    <PackageIcon class="w-16 h-16 text-[var(--maya-text-muted)] mx-auto mb-4" />
                    <p class="text-[var(--maya-text-muted)]">No se encontraron paquetes</p>
                </div>

                <!-- Botón Continuar -->
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--maya-bg-base)] border-t border-[var(--maya-border)]" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
                    <button
                        @click="cambiarVista('confirmar')"
                        :disabled="paquetesSeleccionados.length === 0"
                        class="w-full py-3.5 rounded-xl font-semibold text-white transition-all active:scale-[0.98] disabled:opacity-50"
                        :class="paquetesSeleccionados.length > 0 ? 'bg-[var(--maya-primary)]' : 'bg-gray-400'"
                    >
                        Continuar ({{ paquetesSeleccionados.length }})
                    </button>
                </div>
            </div>

            <!-- Vista: Confirmar -->
            <div v-if="vistaActual === 'confirmar'" class="space-y-4 pb-24">
                <!-- Header con Back -->
                <div class="px-4 pt-4 flex items-center gap-3">
                    <button
                        @click="cambiarVista('paquetes')"
                        class="w-10 h-10 rounded-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] flex items-center justify-center"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-[var(--maya-text-main)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Confirmar</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">Paso 3 de 3</p>
                    </div>
                </div>

                <!-- Resumen -->
                <div class="px-4 space-y-3">
                    <!-- Mensajero -->
                    <div class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                        <p class="text-xs text-[var(--maya-text-muted)] mb-2">Mensajero</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[var(--maya-primary)] flex items-center justify-center text-white font-semibold">
                                {{ mensajeroSeleccionado?.full_name.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-semibold text-[var(--maya-text-main)]">{{ mensajeroSeleccionado?.full_name }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">{{ mensajeroSeleccionado?.vehicle_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-xl font-bold text-[var(--maya-text-main)]">{{ totalPaquetesSeleccionados }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Paquetes</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-xl font-bold text-[var(--maya-text-main)]">{{ pesoTotal }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Kg</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-xl font-bold text-[var(--maya-text-main)]">${{ valorTotal }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Valor</p>
                        </div>
                    </div>

                    <!-- Lista de Paquetes -->
                    <div class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                        <p class="text-xs text-[var(--maya-text-muted)] mb-3">Paquetes seleccionados</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <div
                                v-for="paqueteId in paquetesSeleccionados"
                                :key="paqueteId"
                                class="flex items-center justify-between py-2 border-b border-[var(--maya-border)] last:border-0"
                            >
                                <div>
                                    <p class="text-sm font-mono text-[var(--maya-primary)]">
                                        {{ paquetesDisponibles.find(p => p.id === paqueteId)?.tracking_number }}
                                    </p>
                                    <p class="text-xs text-[var(--maya-text-muted)]">
                                        {{ paquetesDisponibles.find(p => p.id === paqueteId)?.recipient_name }}
                                    </p>
                                </div>
                                <button
                                    @click.stop="togglePaquete(paqueteId)"
                                    class="text-red-500 text-xs px-2 py-1"
                                >
                                    Quitar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nota -->
                    <div class="p-3 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                        <p class="text-xs text-yellow-800 dark:text-yellow-200">
                            Al confirmar, se formalizará la custodia de los paquetes al mensajero.
                        </p>
                    </div>
                </div>

                <!-- Botón Crear -->
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--maya-bg-base)] border-t border-[var(--maya-border)]" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
                    <button
                        @click="crearManifiesto"
                        :disabled="isProcesando"
                        class="w-full py-3.5 rounded-xl font-semibold text-white bg-[var(--maya-success)] transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        <span v-if="isProcesando" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ isProcesando ? 'Creando...' : 'Crear Manifiesto' }}
                    </button>
                </div>
            </div>

            <!-- Vista: Manifiestos -->
            <div v-if="vistaActual === 'manifiestos'" class="space-y-4">
                <!-- Header con Back -->
                <div class="px-4 pt-4 flex items-center gap-3">
                    <button
                        @click="cambiarVista('inicio')"
                        class="w-10 h-10 rounded-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] flex items-center justify-center"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-[var(--maya-text-main)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Manifiestos</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">{{ manifiestosHoy.length }} manifiestos hoy</p>
                    </div>
                </div>

                <!-- Lista de Manifiestos -->
                <div class="px-4 space-y-3">
                    <div
                        v-for="manifiesto in manifiestosHoy"
                        :key="manifiesto.id"
                        @click="verDetalleManifiesto(manifiesto)"
                        class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] active:scale-[0.98] transition-transform"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono text-xs text-[var(--maya-text-muted)]">{{ manifiesto.id }}</span>
                            <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getStatusBg(manifiesto.status)]">
                                {{ manifiesto.status }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[var(--maya-primary-alpha)] flex items-center justify-center">
                                <UserIcon class="w-5 h-5 text-[var(--maya-primary)]" />
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-[var(--maya-text-main)]">{{ manifiesto.messenger.full_name }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">{{ manifiesto.total_items }} paquetes • {{ new Date(manifiesto.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="manifiestosHoy.length === 0" class="px-4 py-12 text-center">
                    <DocumentTextIcon class="w-16 h-16 text-[var(--maya-text-muted)] mx-auto mb-4" />
                    <p class="text-[var(--maya-text-muted)]">No hay manifiestos hoy</p>
                </div>
            </div>

            <!-- Vista: Detalle de Manifiesto -->
            <div v-if="vistaActual === 'detalle' && manifiestoDetalle" class="space-y-4 pb-24">
                <!-- Header con Back -->
                <div class="px-4 pt-4 flex items-center gap-3">
                    <button
                        @click="cambiarVista('manifiestos')"
                        class="w-10 h-10 rounded-full bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] flex items-center justify-center"
                    >
                        <ArrowLeftIcon class="w-5 h-5 text-[var(--maya-text-main)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Detalle</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">{{ manifiestoDetalle.id }}</p>
                    </div>
                </div>

                <!-- Info del Manifiesto -->
                <div class="px-4 space-y-3">
                    <div class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-[var(--maya-text-muted)]">Estado</span>
                            <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBg(manifiestoDetalle.status)]">
                                {{ manifiestoDetalle.status }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-[var(--maya-primary)] flex items-center justify-center text-white font-semibold">
                                {{ manifiestoDetalle.messenger.full_name.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-semibold text-[var(--maya-text-main)]">{{ manifiestoDetalle.messenger.full_name }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">{{ manifiestoDetalle.total_items }} paquetes asignados</p>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-xl font-bold text-[var(--maya-text-main)]">{{ manifiestoDetalle.total_items }}</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Paquetes</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-xl font-bold text-[var(--maya-text-main)]">-</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Kg</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] text-center">
                            <p class="text-xl font-bold text-[var(--maya-text-main)]">-</p>
                            <p class="text-xs text-[var(--maya-text-muted)]">Valor</p>
                        </div>
                    </div>

                    <!-- Hora -->
                    <div class="p-4 rounded-xl bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                        <p class="text-xs text-[var(--maya-text-muted)] mb-1">Creado</p>
                        <p class="text-sm text-[var(--maya-text-main)]">{{ new Date(manifiestoDetalle.created_at).toLocaleString() }}</p>
                    </div>
                </div>

                <!-- Botón Iniciar Despacho -->
                <div v-if="manifiestoDetalle.status === 'Preparando'" class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--maya-bg-base)] border-t border-[var(--maya-border)] safe-area-pb">
                    <button
                        @click="iniciarDespacho(manifiestoDetalle.id)"
                        class="w-full py-3.5 rounded-xl font-semibold text-white bg-[var(--maya-success)] transition-all active:scale-[0.98] flex items-center justify-center gap-2"
                    >
                        <TruckIcon class="w-5 h-5" />
                        Iniciar Despacho
                    </button>
                </div>
            </div>

            <!-- Toast de Notificación -->
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showToast"
                    class="fixed bottom-20 left-4 right-4 z-50 rounded-xl px-4 py-3 shadow-lg flex items-center gap-3"
                    :class="toastType === 'success' ? 'bg-[var(--maya-success)] text-white' : 'bg-red-500 text-white'"
                >
                    <CheckCircleIcon class="w-5 h-5 flex-shrink-0" />
                    <span class="text-sm font-medium">{{ mensajeToast }}</span>
                </div>
            </Transition>
        </div>
    </AdminLayout>
</template>

