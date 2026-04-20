<script setup lang="jsx">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';

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
const mensajeroSeleccionado = ref(null);
const paquetesSeleccionados = ref([]);
const busquedaTracking = ref('');
const filtroZona = ref('todas');
const showModalConfirmacion = ref(false);
const showModalDetalle = ref(false);
const manifiestoDetalle = ref(null);
const isProcesando = ref(false);
const mensajeExito = ref('');
const showToast = ref(false);

// Computed
const paquetesFiltrados = computed(() => {
    let paquetes = props.paquetesDisponibles;

    // Filtrar por búsqueda de tracking
    if (busquedaTracking.value) {
        const search = busquedaTracking.value.toLowerCase();
        paquetes = paquetes.filter(p =>
            p.tracking_number.toLowerCase().includes(search) ||
            p.recipient_name.toLowerCase().includes(search)
        );
    }

    // Filtrar por zona
    if (filtroZona.value !== 'todas') {
        paquetes = paquetes.filter(p => p.destination_address.includes(filtroZona.value));
    }

    return paquetes;
});

const mensajerosActivos = computed(() => {
    return props.mensajeros.filter(m => m.activo);
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

const puedeCrearManifiesto = computed(() => {
    return mensajeroSeleccionado.value && paquetesSeleccionados.value.length > 0;
});

// Métodos
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

const seleccionarTodos = () => {
    const ids = paquetesFiltrados.value.map(p => p.id);
    const todosSeleccionados = ids.every(id => paquetesSeleccionados.value.includes(id));

    if (todosSeleccionados) {
        paquetesSeleccionados.value = paquetesSeleccionados.value.filter(id => !ids.includes(id));
    } else {
        const nuevos = ids.filter(id => !paquetesSeleccionados.value.includes(id));
        paquetesSeleccionados.value.push(...nuevos);
    }
};

const abrirModalConfirmacion = () => {
    if (puedeCrearManifiesto.value) {
        showModalConfirmacion.value = true;
    }
};

const cerrarModalConfirmacion = () => {
    showModalConfirmacion.value = false;
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

    mensajeExito.value = `Manifiesto creado exitosamente con ${paquetesSeleccionados.value.length} paquetes asignados a ${mensajeroSeleccionado.value.full_name}`;
    showToast.value = true;

    // Reset
    paquetesSeleccionados.value = [];
    mensajeroSeleccionado.value = null;
    cerrarModalConfirmacion();

    setTimeout(() => {
        showToast.value = false;
    }, 4000);

    isProcesando.value = false;
};

const verDetalleManifiesto = (manifiesto) => {
    manifiestoDetalle.value = manifiesto;
    showModalDetalle.value = true;
};

const cerrarModalDetalle = () => {
    showModalDetalle.value = false;
    manifiestoDetalle.value = null;
};

const iniciarDespacho = (manifiestoId) => {
    // Actualizar estado a "En ruta"
    const idx = props.manifiestosHoy.findIndex(m => m.id === manifiestoId);
    if (idx > -1) {
        props.manifiestosHoy[idx].status = 'En ruta';
    }
    cerrarModalDetalle();

    mensajeExito.value = 'Despacho iniciado. El mensajero ha sido notificado.';
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

const getStatusColor = (status) => {
    const colors = {
        'Preparando': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'En ruta': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'Completado': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
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

function MagnifyingGlassIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
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

function XMarkIcon(props) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
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
</script>

<template>
    <Head title="Asignación de Transporte" />

    <AdminLayout title="Asignación de Transporte">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-[var(--maya-text-main)] sm:truncate sm:text-3xl sm:tracking-tight">
                        Asignación de Paquetes
                    </h2>
                    <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                        Asigne paquetes a mensajeros para formalizar el inicio del despacho
                    </p>
                </div>
            </div>

            <!-- Resumen Estadísticas -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Mensajeros Activos -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-[var(--maya-primary)] p-3">
                            <TruckIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Mensajeros Activos</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-text-main)]">{{ mensajerosActivos.length }}</p>
                    </dd>
                </div>

                <!-- Paquetes Disponibles -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-[var(--maya-success)] p-3">
                            <PackageIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Paquetes Disponibles</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-text-main)]">{{ paquetesDisponibles.length }}</p>
                    </dd>
                </div>

                <!-- Manifiestos Hoy -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-blue-500 p-3">
                            <DocumentTextIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">Manifiestos Hoy</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-text-main)]">{{ manifiestosHoy.length }}</p>
                    </dd>
                </div>

                <!-- En Ruta -->
                <div class="relative overflow-hidden rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] px-4 pb-4 pt-5 shadow-sm sm:px-6 sm:pt-6">
                    <dt>
                        <div class="absolute rounded-md bg-orange-500 p-3">
                            <ArrowRightIcon class="h-6 w-6 text-white" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-[var(--maya-text-muted)]">En Ruta</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline">
                        <p class="text-2xl font-semibold text-[var(--maya-text-main)]">{{ manifiestosHoy.filter(m => m.status === 'En ruta').length }}</p>
                    </dd>
                </div>
            </div>

            <!-- Contenido Principal: 2 Columnas -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Mensajeros -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Selección de Mensajero -->
                    <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                        <div class="px-4 py-5 sm:px-6 border-b border-[var(--maya-border)]">
                            <h3 class="text-base font-semibold leading-6 text-[var(--maya-text-main)]">
                                1. Seleccionar Mensajero
                            </h3>
                            <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                                Elija el transporte para la asignación
                            </p>
                        </div>
                        <div class="px-4 py-5 sm:p-6">
                            <div class="space-y-3">
                                <div
                                    v-for="mensajero in mensajerosActivos"
                                    :key="mensajero.id"
                                    @click="seleccionarMensajero(mensajero)"
                                    :class="[
                                        'relative flex items-center space-x-3 rounded-lg border p-3 cursor-pointer transition-all duration-200',
                                        mensajeroSeleccionado?.id === mensajero.id
                                            ? 'border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)] ring-1 ring-[var(--maya-primary)]'
                                            : 'border-[var(--maya-border)] bg-[var(--maya-bg-base)] hover:border-[var(--maya-primary)] hover:bg-[var(--maya-hover-surface)]'
                                    ]"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-[var(--maya-primary)] flex items-center justify-center text-white font-semibold">
                                            {{ mensajero.full_name.charAt(0) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-[var(--maya-text-main)]">
                                            {{ mensajero.full_name }}
                                        </p>
                                        <p class="text-xs text-[var(--maya-text-muted)]">
                                            {{ mensajero.vehicle_id }} • {{ mensajero.paquetes_asignados_hoy }} asignados hoy
                                        </p>
                                    </div>
                                    <div v-if="mensajeroSeleccionado?.id === mensajero.id">
                                        <CheckCircleIcon class="h-5 w-5 text-[var(--maya-primary)]" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de Selección -->
                    <div v-if="mensajeroSeleccionado || paquetesSeleccionados.length > 0" class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                        <div class="px-4 py-5 sm:px-6 border-b border-[var(--maya-border)]">
                            <h3 class="text-base font-semibold leading-6 text-[var(--maya-text-main)]">
                                Resumen de Asignación
                            </h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6 space-y-4">
                            <div v-if="mensajeroSeleccionado" class="flex items-center justify-between text-sm">
                                <span class="text-[var(--maya-text-muted)]">Mensajero:</span>
                                <span class="font-medium text-[var(--maya-text-main)]">{{ mensajeroSeleccionado.full_name }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-[var(--maya-text-muted)]">Paquetes:</span>
                                <span class="font-medium text-[var(--maya-text-main)]">{{ totalPaquetesSeleccionados }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-[var(--maya-text-muted)]">Peso Total:</span>
                                <span class="font-medium text-[var(--maya-text-main)]">{{ pesoTotal }} kg</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-[var(--maya-text-muted)]">Valor Total:</span>
                                <span class="font-medium text-[var(--maya-text-main)]">${{ valorTotal }}</span>
                            </div>
                            <div class="pt-4 border-t border-[var(--maya-border)]">
                                <button
                                    @click="abrirModalConfirmacion"
                                    :disabled="!puedeCrearManifiesto"
                                    class="w-full inline-flex justify-center items-center rounded-md px-3 py-2 text-sm font-semibold shadow-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="puedeCrearManifiesto
                                        ? 'bg-[var(--maya-primary)] text-white hover:brightness-110'
                                        : 'bg-gray-300 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                >
                                    <TruckIcon class="w-4 h-4 mr-2" />
                                    Crear Manifiesto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Paquetes Disponibles -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                        <div class="px-4 py-5 sm:px-6 border-b border-[var(--maya-border)]">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold leading-6 text-[var(--maya-text-main)]">
                                        2. Seleccionar Paquetes
                                    </h3>
                                    <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                                        Seleccione los paquetes a incluir en el manifiesto
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="seleccionarTodos"
                                        class="text-xs px-3 py-1.5 rounded-md bg-[var(--maya-bg-base)] border border-[var(--maya-border)] text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] hover:border-[var(--maya-primary)] transition-colors"
                                    >
                                        Seleccionar Todos
                                    </button>
                                    <span v-if="paquetesSeleccionados.length > 0" class="text-xs text-[var(--maya-primary)] font-medium">
                                        {{ paquetesSeleccionados.length }} seleccionados
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="px-4 py-3 sm:px-6 border-b border-[var(--maya-border)] bg-[var(--maya-bg-base)]">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon class="h-4 w-4 text-[var(--maya-text-muted)]" />
                                    </div>
                                    <input
                                        v-model="busquedaTracking"
                                        type="text"
                                        placeholder="Buscar por tracking o destinatario..."
                                        class="block w-full pl-10 pr-3 py-2 text-sm border border-[var(--maya-border)] rounded-md bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:outline-none focus:ring-1 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)]"
                                    />
                                </div>
                                <select
                                    v-model="filtroZona"
                                    class="block w-full sm:w-48 px-3 py-2 text-sm border border-[var(--maya-border)] rounded-md bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)]"
                                >
                                    <option value="todas">Todas las zonas</option>
                                    <option value="Ciudad de Panamá">Ciudad de Panamá</option>
                                    <option value="San Miguelito">San Miguelito</option>
                                    <option value="Colón">Colón</option>
                                </select>
                            </div>
                        </div>

                        <!-- Lista de Paquetes -->
                        <div class="divide-y divide-[var(--maya-border)]">
                            <div
                                v-for="paquete in paquetesFiltrados"
                                :key="paquete.id"
                                @click="togglePaquete(paquete.id)"
                                :class="[
                                    'flex items-center p-4 cursor-pointer transition-colors duration-150',
                                    paquetesSeleccionados.includes(paquete.id)
                                        ? 'bg-[var(--maya-primary-alpha)] hover:bg-[var(--maya-primary-alpha)]'
                                        : 'hover:bg-[var(--maya-hover-surface)]'
                                ]"
                            >
                                <div class="flex-shrink-0 mr-4">
                                    <div
                                        :class="[
                                            'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                                            paquetesSeleccionados.includes(paquete.id)
                                                ? 'bg-[var(--maya-primary)] border-[var(--maya-primary)]'
                                                : 'border-[var(--maya-border)] bg-[var(--maya-bg-surface)]'
                                        ]"
                                    >
                                        <CheckCircleIcon v-if="paquetesSeleccionados.includes(paquete.id)" class="w-3.5 h-3.5 text-white" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono text-sm font-semibold text-[var(--maya-primary)]">
                                            {{ paquete.tracking_number }}
                                        </span>
                                        <span class="text-xs text-[var(--maya-text-muted)]">
                                            {{ paquete.weight_kg }} kg
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-[var(--maya-text-main)] truncate">
                                        {{ paquete.recipient_name }}
                                    </p>
                                    <p class="text-xs text-[var(--maya-text-muted)] flex items-center gap-1 mt-0.5">
                                        <MapPinIcon class="w-3 h-3" />
                                        {{ paquete.destination_address }}
                                    </p>
                                </div>

                                <div class="flex-shrink-0 text-right ml-4">
                                    <p class="text-sm font-semibold text-[var(--maya-text-main)]">
                                        ${{ paquete.total_cost.toFixed(2) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="paquetesFiltrados.length === 0" class="p-8 text-center">
                                <PackageIcon class="w-12 h-12 text-[var(--maya-text-muted)] mx-auto mb-3" />
                                <p class="text-[var(--maya-text-muted)]">No se encontraron paquetes disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manifiestos del Día -->
            <div class="rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)] shadow-sm">
                <div class="px-4 py-5 sm:px-6 border-b border-[var(--maya-border)]">
                    <h3 class="text-base font-semibold leading-6 text-[var(--maya-text-main)]">
                        Manifiestos del Día
                    </h3>
                    <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                        Seguimiento de manifiestos creados hoy
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--maya-border)]">
                        <thead class="bg-[var(--maya-bg-base)]">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase tracking-wider">Mensajero</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase tracking-wider">Paquetes</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[var(--maya-text-muted)] uppercase tracking-wider">Hora</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-[var(--maya-text-muted)] uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--maya-border)]">
                            <tr v-for="manifiesto in manifiestosHoy" :key="manifiesto.id" class="hover:bg-[var(--maya-hover-surface)]">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-[var(--maya-text-main)]">
                                    {{ manifiesto.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--maya-text-main)]">
                                    {{ manifiesto.messenger.full_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--maya-text-muted)]">
                                    {{ manifiesto.total_items }} paquetes
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(manifiesto.status)]">
                                        {{ manifiesto.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--maya-text-muted)]">
                                    {{ new Date(manifiesto.created_at).toLocaleTimeString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <button
                                        @click="verDetalleManifiesto(manifiesto)"
                                        class="text-[var(--maya-primary)] hover:text-[var(--maya-primary)] hover:underline"
                                    >
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <Modal :show="showModalConfirmacion" @close="cerrarModalConfirmacion" max-width="lg">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="rounded-full bg-[var(--maya-primary-alpha)] p-2">
                        <TruckIcon class="w-6 h-6 text-[var(--maya-primary)]" />
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">
                        Confirmar Asignación
                    </h3>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-[var(--maya-text-muted)]">
                        Está a punto de crear un manifiesto con los siguientes datos:
                    </p>

                    <div class="rounded-lg bg-[var(--maya-bg-base)] p-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-[var(--maya-text-muted)]">Mensajero:</span>
                            <span class="font-medium text-[var(--maya-text-main)]">{{ mensajeroSeleccionado?.full_name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[var(--maya-text-muted)]">Vehículo:</span>
                            <span class="font-medium text-[var(--maya-text-main)]">{{ mensajeroSeleccionado?.vehicle_id }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[var(--maya-text-muted)]">Paquetes:</span>
                            <span class="font-medium text-[var(--maya-text-main)]">{{ totalPaquetesSeleccionados }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[var(--maya-text-muted)]">Peso Total:</span>
                            <span class="font-medium text-[var(--maya-text-main)]">{{ pesoTotal }} kg</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[var(--maya-text-muted)]">Valor Total:</span>
                            <span class="font-medium text-[var(--maya-text-main)]">${{ valorTotal }}</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-3">
                        <p class="text-xs text-yellow-800 dark:text-yellow-200">
                            <strong>Nota:</strong> Al confirmar, se formalizará la custodia de los paquetes al mensajero y se iniciará el proceso de despacho.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="cerrarModalConfirmacion"
                        :disabled="isProcesando"
                        class="px-4 py-2 text-sm font-medium text-[var(--maya-text-muted)] bg-[var(--maya-bg-base)] border border-[var(--maya-border)] rounded-md hover:bg-[var(--maya-hover-surface)] transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="crearManifiesto"
                        :disabled="isProcesando"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--maya-primary)] rounded-md hover:brightness-110 transition-all flex items-center gap-2"
                    >
                        <span v-if="isProcesando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ isProcesando ? 'Creando...' : 'Confirmar y Crear' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal de Detalle -->
        <Modal :show="showModalDetalle" @close="cerrarModalDetalle" max-width="lg">
            <div v-if="manifiestoDetalle" class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">
                        Detalle del Manifiesto
                    </h3>
                    <button @click="cerrarModalDetalle" class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-[var(--maya-bg-base)] p-3">
                            <p class="text-xs text-[var(--maya-text-muted)]">ID</p>
                            <p class="text-sm font-mono text-[var(--maya-text-main)]">{{ manifiestoDetalle.id }}</p>
                        </div>
                        <div class="rounded-lg bg-[var(--maya-bg-base)] p-3">
                            <p class="text-xs text-[var(--maya-text-muted)]">Estado</p>
                            <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getStatusColor(manifiestoDetalle.status)]">
                                {{ manifiestoDetalle.status }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-[var(--maya-bg-base)] p-3">
                        <p class="text-xs text-[var(--maya-text-muted)] mb-1">Mensajero Asignado</p>
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded-full bg-[var(--maya-primary)] flex items-center justify-center text-white text-sm font-semibold">
                                {{ manifiestoDetalle.messenger.full_name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[var(--maya-text-main)]">{{ manifiestoDetalle.messenger.full_name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-[var(--maya-bg-base)] p-3">
                        <p class="text-xs text-[var(--maya-text-muted)] mb-2">Resumen</p>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-lg font-semibold text-[var(--maya-text-main)]">{{ manifiestoDetalle.total_items }}</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Paquetes</p>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-[var(--maya-text-main)]">-</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Peso (kg)</p>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-[var(--maya-text-main)]">-</p>
                                <p class="text-xs text-[var(--maya-text-muted)]">Valor ($)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        v-if="manifiestoDetalle.status === 'Preparando'"
                        @click="iniciarDespacho(manifiestoDetalle.id)"
                        class="px-4 py-2 text-sm font-medium text-white bg-[var(--maya-success)] rounded-md hover:brightness-110 transition-all flex items-center gap-2"
                    >
                        <TruckIcon class="w-4 h-4" />
                        Iniciar Despacho
                    </button>
                    <button
                        @click="cerrarModalDetalle"
                        class="px-4 py-2 text-sm font-medium text-[var(--maya-text-main)] bg-[var(--maya-bg-base)] border border-[var(--maya-border)] rounded-md hover:bg-[var(--maya-hover-surface)] transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Toast de Éxito -->
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
                class="fixed bottom-4 right-4 z-50 rounded-lg bg-[var(--maya-success)] text-white px-4 py-3 shadow-lg flex items-center gap-2"
            >
                <CheckCircleIcon class="w-5 h-5" />
                <span class="text-sm font-medium">{{ mensajeExito }}</span>
            </div>
        </Transition>
    </AdminLayout>
</template>