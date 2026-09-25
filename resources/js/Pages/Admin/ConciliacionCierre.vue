<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';

// --- Datos de Maqueta Reactivos ---
const fechaOperacion = ref('2026-03-10');
const showModalDevolucion = ref(false);
const showModalCierreFinal = ref(false);
const toast = ref({ show: false, message: '', type: 'success' });

const paqueteEnEdicion = ref(null);
const motivoSeleccionado = ref('');
const notasDevolucion = ref('');

// Vista de la mesa de trabajo: 'conductor' | 'almacen_consolidado'
const activeTab = ref('conductor');

// Filtro de conductores en barra lateral
const filtroEstadoConductor = ref('todos');
const busquedaConductor = ref('');

// Filtro de paquetes del conductor
const filtroPaquetes = ref('todos'); // 'todos' | 'pendientes' | 'devueltos'

const motivosDevolucion = [
    { id: 'direccion_incorrecta', label: 'Dirección incorrecta o no encontrada' },
    { id: 'cliente_ausente', label: 'Cliente no disponible / ausente' },
    { id: 'rechazado', label: 'Rechazado por el cliente' },
    { id: 'paquete_danado', label: 'Paquete o empaque dañado' },
    { id: 'zona_inaccesible', label: 'Zona peligrosa o inaccesible' },
    { id: 'otro', label: 'Otro motivo operativo' }
];

const conductores = ref([
    {
        id: 1,
        nombre: 'Carlos Méndez',
        vehiculo: 'Moto Honda (M-04)',
        total_asignados: 45,
        entregados: 42,
        estado: 'pendiente', // 'pendiente' | 'liquidado'
        paquetes: [
            {
                id: 101,
                tracking: 'MAYA001234',
                cliente: 'Juan Martínez',
                direccion: 'Calle 50 #12-34, Bella Vista',
                estado: 'pendiente', // 'pendiente' | 'devuelto'
                motivo: null,
                resolucion: null // 'almacen' | 'reprogramado' | null
            },
            {
                id: 102,
                tracking: 'MAYA001235',
                cliente: 'María López',
                direccion: 'Av. Balboa, PH Yoo, Apto 14B',
                estado: 'pendiente',
                motivo: null,
                resolucion: null
            },
            {
                id: 103,
                tracking: 'MAYA001236',
                cliente: 'Pedro Sánchez',
                direccion: 'Carrera 7 #89-12, San Francisco',
                estado: 'devuelto',
                motivo: 'Dirección incorrecta o no encontrada',
                resolucion: null
            }
        ]
    },
    {
        id: 2,
        nombre: 'María González',
        vehiculo: 'Van Nissan (V-02)',
        total_asignados: 38,
        entregados: 35,
        estado: 'pendiente',
        paquetes: [
            {
                id: 104,
                tracking: 'MAYA001237',
                cliente: 'Laura Torres',
                direccion: 'Calle 100 #23-45, Vía España',
                estado: 'devuelto',
                motivo: 'Cliente no disponible / ausente',
                resolucion: null
            },
            {
                id: 105,
                tracking: 'MAYA001238',
                cliente: 'Roberto Díaz',
                direccion: 'Vía Brasil #10, Obarrio',
                estado: 'pendiente',
                motivo: null,
                resolucion: null
            }
        ]
    },
    {
        id: 3,
        nombre: 'Juan Pérez',
        vehiculo: 'Camión Isuzu (C-01)',
        total_asignados: 42,
        entregados: 38,
        estado: 'liquidado',
        paquetes: [
            {
                id: 106,
                tracking: 'MAYA001239',
                cliente: 'Ana Castillo',
                direccion: 'Costa del Este, Ave. Principal',
                estado: 'devuelto',
                motivo: 'Rechazado por el cliente',
                resolucion: 'almacen'
            },
            {
                id: 107,
                tracking: 'MAYA001240',
                cliente: 'Luis Gómez',
                direccion: 'Clayton, Edif 120, Ancón',
                estado: 'devuelto',
                motivo: 'Zona peligrosa o inaccesible',
                resolucion: 'reprogramado'
            },
            {
                id: 108,
                tracking: 'MAYA001243',
                cliente: 'Carla Batista',
                direccion: 'El Dorado, Calle 4ta',
                estado: 'devuelto',
                motivo: 'Cliente no disponible / ausente',
                resolucion: 'almacen'
            },
            {
                id: 109,
                tracking: 'MAYA001244',
                cliente: 'Esteban Arce',
                direccion: 'Brisas del Golf, Mz 15',
                estado: 'devuelto',
                motivo: 'Dirección incorrecta o no encontrada',
                resolucion: 'reprogramado'
            }
        ]
    },
    {
        id: 4,
        nombre: 'Ana Rodríguez',
        vehiculo: 'Moto Yamaha (M-07)',
        total_asignados: 31,
        entregados: 29,
        estado: 'pendiente',
        paquetes: [
            {
                id: 110,
                tracking: 'MAYA001241',
                cliente: 'Elena Vega',
                direccion: 'El Cangrejo, Calle Andrés Bello',
                estado: 'pendiente',
                motivo: null,
                resolucion: null
            },
            {
                id: 111,
                tracking: 'MAYA001242',
                cliente: 'Mario Moreno',
                direccion: 'Paitilla, Calle 53 Este',
                estado: 'devuelto',
                motivo: 'Paquete o empaque dañado',
                resolucion: null
            }
        ]
    }
]);

const conductorSeleccionadoId = ref(1);

// --- Computados ---
const conductorSeleccionado = computed(() => {
    return conductores.value.find(c => c.id === conductorSeleccionadoId.value) || conductores.value[0];
});

const conductoresFiltrados = computed(() => {
    return conductores.value.filter(c => {
        const matchesEstado = filtroEstadoConductor.value === 'todos' || c.estado === filtroEstadoConductor.value;
        const matchesBusqueda = c.nombre.toLowerCase().includes(busquedaConductor.value.toLowerCase()) ||
            c.vehiculo.toLowerCase().includes(busquedaConductor.value.toLowerCase());
        return matchesEstado && matchesBusqueda;
    });
});

const paquetesConductorFiltrados = computed(() => {
    if (!conductorSeleccionado.value) return [];
    const pkgs = conductorSeleccionado.value.paquetes || [];
    if (filtroPaquetes.value === 'pendientes') {
        return pkgs.filter(p => p.estado === 'pendiente');
    }
    if (filtroPaquetes.value === 'devueltos') {
        return pkgs.filter(p => p.estado === 'devuelto');
    }
    return pkgs;
});

// Consolidado de todos los devueltos en la jornada para el bodeguero
const todasLasDevoluciones = computed(() => {
    const list = [];
    conductores.value.forEach(c => {
        c.paquetes.forEach(p => {
            if (p.estado === 'devuelto') {
                list.push({
                    ...p,
                    conductorNombre: c.nombre,
                    conductorId: c.id
                });
            }
        });
    });
    return list;
});

const devueltosSinResolucion = computed(() => {
    return todasLasDevoluciones.value.filter(p => !p.resolucion).length;
});

// Métricas de nivel superior
const metrics = computed(() => {
    let totalAsignados = 0;
    let entregados = 0;
    let pendientes = 0;
    let devueltos = 0;

    conductores.value.forEach(c => {
        totalAsignados += c.total_asignados;
        entregados += c.entregados;
        const cPend = c.paquetes.filter(p => p.estado === 'pendiente').length;
        const cDev = c.paquetes.filter(p => p.estado === 'devuelto').length;
        pendientes += cPend;
        devueltos += cDev;
    });

    const totalConductores = conductores.value.length;
    const liquidados = conductores.value.filter(c => c.estado === 'liquidado').length;
    const porcentajeProgreso = totalConductores > 0 ? Math.round((liquidados / totalConductores) * 100) : 0;
    const tasaEntrega = totalAsignados > 0 ? Math.round((entregados / totalAsignados) * 100) : 0;

    return {
        totalAsignados,
        entregados,
        pendientes,
        devueltos,
        totalConductores,
        liquidados,
        porcentajeProgreso,
        tasaEntrega
    };
});

// --- Métodos de Interacción ---
const triggerToast = (msg, type = 'success') => {
    toast.value = { show: true, message: msg, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3200);
};

const abrirModalDevolucion = (paquete) => {
    paqueteEnEdicion.value = paquete;
    motivoSeleccionado.value = paquete.motivo || '';
    notasDevolucion.value = '';
    showModalDevolucion.value = true;
};

const cerrarModalDevolucion = () => {
    showModalDevolucion.value = false;
    paqueteEnEdicion.value = null;
    motivoSeleccionado.value = '';
    notasDevolucion.value = '';
};

const guardarDevolucion = () => {
    if (!paqueteEnEdicion.value || !motivoSeleccionado.value) return;

    paqueteEnEdicion.value.estado = 'devuelto';
    paqueteEnEdicion.value.motivo = motivoSeleccionado.value;
    paqueteEnEdicion.value.resolucion = null;

    triggerToast(`Guía ${paqueteEnEdicion.value.tracking} marcada como devuelta.`);
    cerrarModalDevolucion();
};

const asignarResolucion = (paquete, tipo) => {
    // tipo: 'almacen' | 'reprogramado'
    paquete.resolucion = tipo;
    const texto = tipo === 'almacen' ? 're-ingresado al Almacén' : 'reprogramado para mañana';
    triggerToast(`Paquete ${paquete.tracking} ${texto}.`);
};

const liquidarConductor = (conductor) => {
    const pendientesSinResolver = conductor.paquetes.filter(p => p.estado === 'pendiente');
    if (pendientesSinResolver.length > 0) {
        triggerToast(`No puedes liquidar a ${conductor.nombre}: aún tiene ${pendientesSinResolver.length} paquete(s) sin clasificar.`, 'warning');
        return;
    }

    conductor.estado = 'liquidado';
    triggerToast(`Ruta de ${conductor.nombre} liquidada correctamente.`);
};

const reactivarConductor = (conductor) => {
    conductor.estado = 'pendiente';
    triggerToast(`Ruta de ${conductor.nombre} marcada como pendiente de revisión.`);
};

const reingresarTodosAlmacen = () => {
    let count = 0;
    todasLasDevoluciones.value.forEach(p => {
        if (!p.resolucion) {
            p.resolucion = 'almacen';
            count++;
        }
    });
    triggerToast(`${count} paquetes re-ingresados al inventario de bodega.`);
};

const confirmarCierreOperativo = () => {
    showModalCierreFinal.value = false;
    triggerToast('¡Cierre operativo de la jornada finalizado con éxito!');
};
</script>

<template>
    <Head title="Conciliación de Cierre" />

    <AdminLayout title="Conciliación de Cierre">
        <!-- Toast de notificación discreto -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toast.show"
                class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border text-sm font-medium"
                :class="[
                    toast.type === 'warning'
                        ? 'bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:border-amber-800'
                        : 'bg-emerald-50 text-emerald-900 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-200 dark:border-emerald-800'
                ]"
            >
                <font-awesome-icon
                    :icon="toast.type === 'warning' ? ['fas', 'triangle-exclamation'] : ['fas', 'circle-check']"
                    class="text-base"
                />
                <span>{{ toast.message }}</span>
            </div>
        </transition>

        <div class="space-y-5">
            <!-- 1. Encabezado Unificado y Compacto -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-[var(--maya-bg-surface)] p-4 sm:p-5 rounded-xl border border-[var(--maya-border)]">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl sm:text-2xl font-bold text-[var(--maya-text-main)]">
                            Conciliación de Cierre
                        </h1>
                        <!-- Pill de Progreso de Liquidación -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] border border-[var(--maya-primary)]/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--maya-primary)] animate-pulse"></span>
                            {{ metrics.liquidados }} de {{ metrics.totalConductores }} liquidados ({{ metrics.porcentajeProgreso }}%)
                        </span>
                    </div>
                    <div class="flex items-center gap-4 mt-1.5 text-xs text-[var(--maya-text-muted)]">
                        <span class="flex items-center gap-1.5">
                            <font-awesome-icon :icon="['fas', 'calendar']" class="text-xs text-[var(--maya-primary)]" />
                            Jornada: <strong class="text-[var(--maya-text-main)]">{{ fechaOperacion }}</strong>
                        </span>
                        <span>•</span>
                        <span>Auditoría física vs. digital de última milla</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showModalCierreFinal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-[var(--maya-primary)] hover:brightness-110 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2"
                    >
                        <font-awesome-icon :icon="['fas', 'clipboard-check']" />
                        <span>Finalizar Cierre del Día</span>
                    </button>
                </div>
            </div>

            <!-- 2. KPIs Globales en 1 Fila Limpia (Sin cajas de colores invasivos) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Total Paquetes -->
                <div class="bg-[var(--maya-bg-surface)] p-4 rounded-xl border border-[var(--maya-border)] shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-[var(--maya-text-muted)]">Carga Total en Ruta</span>
                        <div class="w-8 h-8 rounded-lg bg-[var(--maya-primary-alpha)] flex items-center justify-center text-[var(--maya-primary)] text-sm">
                            <font-awesome-icon :icon="['fas', 'box']" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-[var(--maya-text-main)]">{{ metrics.totalAsignados }}</span>
                        <span class="text-xs text-[var(--maya-text-muted)]">paquetes</span>
                    </div>
                </div>

                <!-- Entregados -->
                <div class="bg-[var(--maya-bg-surface)] p-4 rounded-xl border border-[var(--maya-border)] shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-[var(--maya-text-muted)]">Entregados con Éxito</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 text-sm">
                            <font-awesome-icon :icon="['fas', 'circle-check']" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-emerald-600">{{ metrics.entregados }}</span>
                        <span class="text-xs font-medium text-emerald-600/80 bg-emerald-500/10 px-1.5 py-0.5 rounded">
                            {{ metrics.tasaEntrega }}%
                        </span>
                    </div>
                </div>

                <!-- Pendientes sin resolver -->
                <div class="bg-[var(--maya-bg-surface)] p-4 rounded-xl border border-[var(--maya-border)] shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-[var(--maya-text-muted)]">Pendientes por Conciliar</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-600 text-sm">
                            <font-awesome-icon :icon="['fas', 'clock']" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-amber-600">{{ metrics.pendientes }}</span>
                        <span class="text-xs text-[var(--maya-text-muted)]">sin clasificar</span>
                    </div>
                </div>

                <!-- En Devolución -->
                <div class="bg-[var(--maya-bg-surface)] p-4 rounded-xl border border-[var(--maya-border)] shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-[var(--maya-text-muted)]">Devoluciones Físicas</span>
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 flex items-center justify-center text-rose-600 text-sm">
                            <font-awesome-icon :icon="['fas', 'rotate-left']" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-rose-600">{{ metrics.devueltos }}</span>
                        <span
                            v-if="devueltosSinResolucion > 0"
                            class="text-xs text-rose-600/90 bg-rose-500/10 px-1.5 py-0.5 rounded font-medium"
                        >
                            {{ devueltosSinResolucion }} por ingresar
                        </span>
                        <span v-else class="text-xs text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded font-medium">
                            Al día
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Mesa de Trabajo Principal: Maestro-Detalle sin duplicación -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                <!-- Columna Izquierda: Lista de Conductores (4 de 12 columnas) -->
                <div class="lg:col-span-4 bg-[var(--maya-bg-surface)] rounded-xl border border-[var(--maya-border)] shadow-xs overflow-hidden">
                    <div class="p-3.5 border-b border-[var(--maya-border)] space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-[var(--maya-text-main)] flex items-center gap-2">
                                <font-awesome-icon :icon="['fas', 'truck']" class="text-xs text-[var(--maya-primary)]" />
                                Mensajeros en Ruta ({{ conductores.length }})
                            </h2>
                            <!-- Segmented filter -->
                            <div class="inline-flex rounded-lg bg-[var(--maya-bg-base)] p-0.5 border border-[var(--maya-border)] text-xs">
                                <button
                                    type="button"
                                    @click="filtroEstadoConductor = 'todos'"
                                    :class="[
                                        'px-2 py-1 rounded-md transition-all font-medium',
                                        filtroEstadoConductor === 'todos'
                                            ? 'bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] shadow-xs'
                                            : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'
                                    ]"
                                >
                                    Todos
                                </button>
                                <button
                                    type="button"
                                    @click="filtroEstadoConductor = 'pendiente'"
                                    :class="[
                                        'px-2 py-1 rounded-md transition-all font-medium',
                                        filtroEstadoConductor === 'pendiente'
                                            ? 'bg-[var(--maya-bg-surface)] text-amber-600 shadow-xs'
                                            : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'
                                    ]"
                                >
                                    Pendientes
                                </button>
                                <button
                                    type="button"
                                    @click="filtroEstadoConductor = 'liquidado'"
                                    :class="[
                                        'px-2 py-1 rounded-md transition-all font-medium',
                                        filtroEstadoConductor === 'liquidado'
                                            ? 'bg-[var(--maya-bg-surface)] text-emerald-600 shadow-xs'
                                            : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'
                                    ]"
                                >
                                    Liquidados
                                </button>
                            </div>
                        </div>

                        <!-- Buscador rápido -->
                        <div class="relative">
                            <font-awesome-icon
                                :icon="['fas', 'magnifying-glass']"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--maya-text-muted)]"
                            />
                            <input
                                v-model="busquedaConductor"
                                type="text"
                                placeholder="Buscar mensajero o vehículo..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] text-[var(--maya-text-main)] focus:ring-1 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)]"
                            />
                        </div>
                    </div>

                    <!-- Lista scrollable -->
                    <div class="divide-y divide-[var(--maya-border)] max-h-[560px] overflow-y-auto">
                        <div
                            v-for="conductor in conductoresFiltrados"
                            :key="conductor.id"
                            @click="conductorSeleccionadoId = conductor.id; activeTab = 'conductor'"
                            :class="[
                                'p-3.5 cursor-pointer transition-all flex items-center justify-between text-left select-none',
                                conductorSeleccionadoId === conductor.id && activeTab === 'conductor'
                                    ? 'bg-[var(--maya-primary-alpha)] border-l-4 border-[var(--maya-primary)]'
                                    : 'hover:bg-[var(--maya-hover-surface)] border-l-4 border-transparent'
                            ]"
                        >
                            <div class="min-w-0 pr-2">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-[var(--maya-text-main)] truncate">
                                        {{ conductor.nombre }}
                                    </p>
                                    <span
                                        :class="[
                                            'text-[10px] px-1.5 py-0.5 rounded font-medium',
                                            conductor.estado === 'liquidado'
                                                ? 'bg-emerald-500/10 text-emerald-600'
                                                : 'bg-amber-500/10 text-amber-600'
                                        ]"
                                    >
                                        {{ conductor.estado === 'liquidado' ? 'Liquidado' : 'Pendiente' }}
                                    </span>
                                </div>
                                <p class="text-xs text-[var(--maya-text-muted)] mt-0.5 truncate">
                                    {{ conductor.vehiculo }}
                                </p>
                                <div class="flex items-center gap-3 mt-1.5 text-[11px] text-[var(--maya-text-muted)]">
                                    <span>
                                        <strong class="text-emerald-600 font-semibold">{{ conductor.entregados }}</strong>/{{ conductor.total_asignados }} entregados
                                    </span>
                                    <span
                                        v-if="conductor.paquetes.some(p => p.estado === 'devuelto')"
                                        class="text-rose-600 font-medium"
                                    >
                                        {{ conductor.paquetes.filter(p => p.estado === 'devuelto').length }} devueltos
                                    </span>
                                </div>
                            </div>

                            <font-awesome-icon
                                :icon="['fas', 'chevron-right']"
                                :class="[
                                    'text-xs transition-transform',
                                    conductorSeleccionadoId === conductor.id && activeTab === 'conductor'
                                        ? 'text-[var(--maya-primary)] translate-x-0.5'
                                        : 'text-[var(--maya-text-muted)] opacity-40'
                                ]"
                            />
                        </div>

                        <div v-if="conductoresFiltrados.length === 0" class="p-6 text-center text-xs text-[var(--maya-text-muted)]">
                            No se encontraron mensajeros con los filtros actuales.
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Mesa de Liquidación & Excepciones (8 de 12 columnas) -->
                <div class="lg:col-span-8 space-y-4">
                    <!-- Selector de Vista Superior de la Mesa -->
                    <div class="flex items-center justify-between bg-[var(--maya-bg-surface)] p-2 rounded-xl border border-[var(--maya-border)]">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="activeTab = 'conductor'"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-2',
                                    activeTab === 'conductor'
                                        ? 'bg-[var(--maya-primary)] text-white shadow-xs'
                                        : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]'
                                ]"
                            >
                                <font-awesome-icon :icon="['fas', 'user']" />
                                <span>Liquidación por Conductor</span>
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'almacen_consolidado'"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-2',
                                    activeTab === 'almacen_consolidado'
                                        ? 'bg-[var(--maya-primary)] text-white shadow-xs'
                                        : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]'
                                ]"
                            >
                                <font-awesome-icon :icon="['fas', 'warehouse']" />
                                <span>Consolidado Re-ingreso Bodega</span>
                                <span
                                    v-if="devueltosSinResolucion > 0"
                                    class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-rose-500 text-white"
                                >
                                    {{ devueltosSinResolucion }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 1: Liquidación del Conductor Seleccionado -->
                    <div
                        v-if="activeTab === 'conductor' && conductorSeleccionado"
                        class="bg-[var(--maya-bg-surface)] rounded-xl border border-[var(--maya-border)] shadow-xs overflow-hidden"
                    >
                        <!-- Cabecera del Conductor -->
                        <div class="p-4 sm:p-5 border-b border-[var(--maya-border)] bg-[var(--maya-bg-base)]/40">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2.5">
                                        <h2 class="text-base sm:text-lg font-bold text-[var(--maya-text-main)]">
                                            {{ conductorSeleccionado.nombre }}
                                        </h2>
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded text-xs font-semibold',
                                                conductorSeleccionado.estado === 'liquidado'
                                                    ? 'bg-emerald-500/10 text-emerald-600'
                                                    : 'bg-amber-500/10 text-amber-600'
                                            ]"
                                        >
                                            {{ conductorSeleccionado.estado === 'liquidado' ? 'Liquidado' : 'Pendiente de Cierre' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-[var(--maya-text-muted)] mt-0.5">
                                        Vehículo asignado: <strong class="text-[var(--maya-text-main)]">{{ conductorSeleccionado.vehiculo }}</strong>
                                    </p>
                                </div>

                                <!-- Botón de Liquidar Carga -->
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="conductorSeleccionado.estado === 'pendiente'"
                                        type="button"
                                        @click="liquidarConductor(conductorSeleccionado)"
                                        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition-all"
                                    >
                                        <font-awesome-icon :icon="['fas', 'circle-check']" />
                                        <span>Liquidar Carga</span>
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="reactivarConductor(conductorSeleccionado)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] border border-[var(--maya-border)] hover:bg-[var(--maya-hover-surface)] transition-all"
                                    >
                                        <font-awesome-icon :icon="['fas', 'rotate-left']" />
                                        <span>Reabrir Ruta</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Métricas compactas del conductor en una sola línea -->
                            <div class="grid grid-cols-4 gap-2 mt-4 pt-3 border-t border-[var(--maya-border)] text-center">
                                <div class="p-2 rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                                    <span class="block text-lg font-bold text-[var(--maya-text-main)]">{{ conductorSeleccionado.total_asignados }}</span>
                                    <span class="text-[11px] text-[var(--maya-text-muted)]">Asignados</span>
                                </div>
                                <div class="p-2 rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                                    <span class="block text-lg font-bold text-emerald-600">{{ conductorSeleccionado.entregados }}</span>
                                    <span class="text-[11px] text-[var(--maya-text-muted)]">Entregados</span>
                                </div>
                                <div class="p-2 rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                                    <span class="block text-lg font-bold text-amber-600">
                                        {{ conductorSeleccionado.paquetes.filter(p => p.estado === 'pendiente').length }}
                                    </span>
                                    <span class="text-[11px] text-[var(--maya-text-muted)]">Pendientes</span>
                                </div>
                                <div class="p-2 rounded-lg bg-[var(--maya-bg-surface)] border border-[var(--maya-border)]">
                                    <span class="block text-lg font-bold text-rose-600">
                                        {{ conductorSeleccionado.paquetes.filter(p => p.estado === 'devuelto').length }}
                                    </span>
                                    <span class="text-[11px] text-[var(--maya-text-muted)]">Devueltos</span>
                                </div>
                            </div>
                        </div>

                        <!-- Barra de Herramientas de la Tabla -->
                        <div class="px-4 py-3 border-b border-[var(--maya-border)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                                Paquetes en Incidencia / No Entregados ({{ conductorSeleccionado.paquetes.length }})
                            </h3>
                            <div class="inline-flex rounded-lg bg-[var(--maya-bg-base)] p-0.5 border border-[var(--maya-border)] text-xs">
                                <button
                                    type="button"
                                    @click="filtroPaquetes = 'todos'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md transition-all font-medium',
                                        filtroPaquetes === 'todos'
                                            ? 'bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] shadow-xs'
                                            : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'
                                    ]"
                                >
                                    Todos ({{ conductorSeleccionado.paquetes.length }})
                                </button>
                                <button
                                    type="button"
                                    @click="filtroPaquetes = 'pendientes'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md transition-all font-medium',
                                        filtroPaquetes === 'pendientes'
                                            ? 'bg-[var(--maya-bg-surface)] text-amber-600 shadow-xs'
                                            : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'
                                    ]"
                                >
                                    Pendientes ({{ conductorSeleccionado.paquetes.filter(p => p.estado === 'pendiente').length }})
                                </button>
                                <button
                                    type="button"
                                    @click="filtroPaquetes = 'devueltos'"
                                    :class="[
                                        'px-2.5 py-1 rounded-md transition-all font-medium',
                                        filtroPaquetes === 'devueltos'
                                            ? 'bg-[var(--maya-bg-surface)] text-rose-600 shadow-xs'
                                            : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'
                                    ]"
                                >
                                    Devueltos ({{ conductorSeleccionado.paquetes.filter(p => p.estado === 'devuelto').length }})
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de Paquetes -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[var(--maya-border)] text-left text-xs">
                                <thead class="bg-[var(--maya-bg-base)]/50 text-[var(--maya-text-muted)] font-semibold">
                                    <tr>
                                        <th class="px-4 py-2.5">Guía / Tracking</th>
                                        <th class="px-4 py-2.5">Destinatario</th>
                                        <th class="px-4 py-2.5">Estado / Motivo</th>
                                        <th class="px-4 py-2.5 text-right">Acción de Almacén</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--maya-border)]">
                                    <tr
                                        v-for="paquete in paquetesConductorFiltrados"
                                        :key="paquete.id"
                                        class="hover:bg-[var(--maya-hover-surface)]/50 transition-colors"
                                    >
                                        <!-- Tracking -->
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-[var(--maya-primary)]">
                                            {{ paquete.tracking }}
                                        </td>

                                        <!-- Destinatario & Dirección -->
                                        <td class="px-4 py-3 max-w-[240px]">
                                            <p class="font-medium text-[var(--maya-text-main)] truncate">
                                                {{ paquete.cliente }}
                                            </p>
                                            <p class="text-[11px] text-[var(--maya-text-muted)] truncate flex items-center gap-1 mt-0.5">
                                                <font-awesome-icon :icon="['fas', 'location-dot']" class="text-[10px]" />
                                                {{ paquete.direccion }}
                                            </p>
                                        </td>

                                        <!-- Estado y Motivo -->
                                        <td class="px-4 py-3">
                                            <div v-if="paquete.estado === 'devuelto'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-500/10 text-rose-600">
                                                    <font-awesome-icon :icon="['fas', 'rotate-left']" class="text-[9px]" />
                                                    Devuelto
                                                </span>
                                                <p class="text-[11px] text-[var(--maya-text-muted)] mt-1 truncate max-w-[200px]" :title="paquete.motivo">
                                                    {{ paquete.motivo }}
                                                </p>
                                            </div>
                                            <div v-else>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500/10 text-amber-600">
                                                    <font-awesome-icon :icon="['fas', 'clock']" class="text-[9px]" />
                                                    Pendiente en ruta
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Acciones Directas en Fila (Sin scroll abajo) -->
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <!-- Si está pendiente: opción de clasificar devolución -->
                                            <button
                                                v-if="paquete.estado === 'pendiente'"
                                                type="button"
                                                @click="abrirModalDevolucion(paquete)"
                                                class="px-2.5 py-1 rounded text-xs font-medium text-[var(--maya-primary)] border border-[var(--maya-primary)]/30 hover:bg-[var(--maya-primary-alpha)] transition-all"
                                            >
                                                Registrar Devolución
                                            </button>

                                            <!-- Si ya es devuelto: opciones de resolución directa -->
                                            <div v-else class="flex items-center justify-end gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="asignarResolucion(paquete, 'almacen')"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-all flex items-center gap-1',
                                                        paquete.resolucion === 'almacen'
                                                            ? 'bg-emerald-600 text-white'
                                                            : 'bg-[var(--maya-bg-base)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] border border-[var(--maya-border)]'
                                                    ]"
                                                    title="Re-ingresar paquete físicamente a bodega"
                                                >
                                                    <font-awesome-icon :icon="['fas', 'warehouse']" class="text-[10px]" />
                                                    <span>{{ paquete.resolucion === 'almacen' ? 'En Bodega' : 'Re-ingresar' }}</span>
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="asignarResolucion(paquete, 'reprogramado')"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-all flex items-center gap-1',
                                                        paquete.resolucion === 'reprogramado'
                                                            ? 'bg-sky-600 text-white'
                                                            : 'bg-[var(--maya-bg-base)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] border border-[var(--maya-border)]'
                                                    ]"
                                                    title="Programar para el reparto de mañana"
                                                >
                                                    <font-awesome-icon :icon="['fas', 'calendar']" class="text-[10px]" />
                                                    <span>{{ paquete.resolucion === 'reprogramado' ? 'Reprogramado' : 'Reprogramar' }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="paquetesConductorFiltrados.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-xs text-[var(--maya-text-muted)]">
                                            No hay paquetes que coincidan con el filtro seleccionado.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: Consolidado de Devoluciones del Día para Bodega -->
                    <div
                        v-else-if="activeTab === 'almacen_consolidado'"
                        class="bg-[var(--maya-bg-surface)] rounded-xl border border-[var(--maya-border)] shadow-xs overflow-hidden"
                    >
                        <div class="p-4 sm:p-5 border-b border-[var(--maya-border)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-[var(--maya-bg-base)]/40">
                            <div>
                                <h2 class="text-base font-bold text-[var(--maya-text-main)] flex items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'warehouse']" class="text-sm text-[var(--maya-primary)]" />
                                    Recepción Consolidada en Almacén
                                </h2>
                                <p class="text-xs text-[var(--maya-text-muted)] mt-0.5">
                                    Control general de todos los paquetes devueltos por los mensajeros al cierre de jornada.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="reingresarTodosAlmacen"
                                    :disabled="devueltosSinResolucion === 0"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-[var(--maya-primary)] hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    <font-awesome-icon :icon="['fas', 'boxes-stacked']" />
                                    <span>Re-ingresar Todos a Bodega</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tabla Consolidada -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[var(--maya-border)] text-left text-xs">
                                <thead class="bg-[var(--maya-bg-base)]/50 text-[var(--maya-text-muted)] font-semibold">
                                    <tr>
                                        <th class="px-4 py-2.5">Guía</th>
                                        <th class="px-4 py-2.5">Mensajero</th>
                                        <th class="px-4 py-2.5">Destinatario</th>
                                        <th class="px-4 py-2.5">Causa de Retorno</th>
                                        <th class="px-4 py-2.5 text-right">Destino / Custodia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--maya-border)]">
                                    <tr
                                        v-for="paquete in todasLasDevoluciones"
                                        :key="paquete.id"
                                        class="hover:bg-[var(--maya-hover-surface)]/50 transition-colors"
                                    >
                                        <td class="px-4 py-3 font-medium text-[var(--maya-primary)] whitespace-nowrap">
                                            {{ paquete.tracking }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-[var(--maya-text-main)]">
                                            {{ paquete.conductorNombre }}
                                        </td>
                                        <td class="px-4 py-3 max-w-[200px]">
                                            <p class="font-medium text-[var(--maya-text-main)] truncate">{{ paquete.cliente }}</p>
                                            <p class="text-[11px] text-[var(--maya-text-muted)] truncate">{{ paquete.direccion }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-[var(--maya-text-main)]">
                                            <span class="inline-flex items-center gap-1 text-xs text-rose-600 font-medium">
                                                {{ paquete.motivo }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    @click="asignarResolucion(paquete, 'almacen')"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-all',
                                                        paquete.resolucion === 'almacen'
                                                            ? 'bg-emerald-600 text-white'
                                                            : 'bg-[var(--maya-bg-base)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] border border-[var(--maya-border)]'
                                                    ]"
                                                >
                                                    {{ paquete.resolucion === 'almacen' ? 'En Bodega ✓' : 'A Bodega' }}
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="asignarResolucion(paquete, 'reprogramado')"
                                                    :class="[
                                                        'px-2 py-1 rounded text-xs font-medium transition-all',
                                                        paquete.resolucion === 'reprogramado'
                                                            ? 'bg-sky-600 text-white'
                                                            : 'bg-[var(--maya-bg-base)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] border border-[var(--maya-border)]'
                                                    ]"
                                                >
                                                    {{ paquete.resolucion === 'reprogramado' ? 'Reprogramado ✓' : 'Reprogramar' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="todasLasDevoluciones.length === 0">
                                        <td colspan="5" class="px-4 py-8 text-center text-xs text-[var(--maya-text-muted)]">
                                            No hay paquetes devueltos en la jornada de hoy.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Registrar Devolución de Paquete -->
        <Modal :show="showModalDevolucion" @close="cerrarModalDevolucion" maxWidth="md">
            <div class="p-5">
                <div class="flex items-center justify-between pb-3 border-b border-[var(--maya-border)]">
                    <h3 class="text-base font-bold text-[var(--maya-text-main)] flex items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'rotate-left']" class="text-rose-600 text-sm" />
                        Registrar Devolución Física
                    </h3>
                    <button
                        type="button"
                        @click="cerrarModalDevolucion"
                        class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] text-sm"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <div v-if="paqueteEnEdicion" class="mt-4 space-y-3.5 text-xs">
                    <!-- Ficha del Paquete -->
                    <div class="p-3 rounded-lg bg-[var(--maya-bg-base)] border border-[var(--maya-border)] space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[var(--maya-text-muted)]">Guía</span>
                            <strong class="text-[var(--maya-primary)] font-semibold">{{ paqueteEnEdicion.tracking }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[var(--maya-text-muted)]">Cliente</span>
                            <span class="text-[var(--maya-text-main)] font-medium">{{ paqueteEnEdicion.cliente }}</span>
                        </div>
                        <div class="flex items-start justify-between">
                            <span class="text-[var(--maya-text-muted)]">Dirección</span>
                            <span class="text-[var(--maya-text-main)] text-right max-w-[200px]">{{ paqueteEnEdicion.direccion }}</span>
                        </div>
                    </div>

                    <!-- Motivo -->
                    <div>
                        <label class="block font-semibold text-[var(--maya-text-main)] mb-1">
                            Motivo de No Entrega <span class="text-rose-600">*</span>
                        </label>
                        <select
                            v-model="motivoSeleccionado"
                            class="w-full text-xs rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] focus:ring-1 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)]"
                        >
                            <option value="">Selecciona el motivo comprobado en físico...</option>
                            <option v-for="m in motivosDevolucion" :key="m.id" :value="m.label">
                                {{ m.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block font-semibold text-[var(--maya-text-main)] mb-1">
                            Notas del Conductor / Supervisor
                        </label>
                        <textarea
                            v-model="notasDevolucion"
                            rows="2"
                            placeholder="Detalle adicional sobre el paquete devuelto..."
                            class="w-full text-xs rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] focus:ring-1 focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)]"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[var(--maya-border)]">
                        <button
                            type="button"
                            @click="cerrarModalDevolucion"
                            class="px-3 py-1.5 rounded-lg border border-[var(--maya-border)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] font-medium transition-all"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="guardarDevolucion"
                            :disabled="!motivoSeleccionado"
                            class="px-3.5 py-1.5 rounded-lg text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed font-semibold shadow-xs transition-all"
                        >
                            Confirmar Devolución
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- MODAL: Confirmación Cierre del Día -->
        <Modal :show="showModalCierreFinal" @close="showModalCierreFinal = false" maxWidth="md">
            <div class="p-5">
                <div class="flex items-center justify-between pb-3 border-b border-[var(--maya-border)]">
                    <h3 class="text-base font-bold text-[var(--maya-text-main)] flex items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'clipboard-check']" class="text-[var(--maya-primary)] text-sm" />
                        Finalizar Cierre de Operaciones
                    </h3>
                    <button
                        type="button"
                        @click="showModalCierreFinal = false"
                        class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] text-sm"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <p class="text-[var(--maya-text-muted)]">
                        Estás a punto de cerrar formalmente la jornada del día <strong>{{ fechaOperacion }}</strong>. Verifica el balance de conciliación:
                    </p>

                    <div class="p-3 rounded-lg bg-[var(--maya-bg-base)] border border-[var(--maya-border)] space-y-2">
                        <div class="flex justify-between">
                            <span class="text-[var(--maya-text-muted)]">Mensajeros liquidados:</span>
                            <strong class="text-[var(--maya-text-main)]">{{ metrics.liquidados }} / {{ metrics.totalConductores }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--maya-text-muted)]">Paquetes entregados:</span>
                            <strong class="text-emerald-600">{{ metrics.entregados }} ({{ metrics.tasaEntrega }}%)</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--maya-text-muted)]">Devoluciones en custodia:</span>
                            <strong class="text-rose-600">{{ metrics.devueltos }} paquetes</strong>
                        </div>
                    </div>

                    <div
                        v-if="metrics.pendientes > 0 || metrics.liquidados < metrics.totalConductores"
                        class="p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300 text-[11px] flex items-start gap-2"
                    >
                        <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 text-xs flex-shrink-0" />
                        <span>Aún existen mensajeros o paquetes sin conciliar. Si confirmas el cierre ahora, las diferencias quedarán registradas en la auditoría del día.</span>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[var(--maya-border)]">
                        <button
                            type="button"
                            @click="showModalCierreFinal = false"
                            class="px-3 py-1.5 rounded-lg border border-[var(--maya-border)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] font-medium transition-all"
                        >
                            Volver
                        </button>
                        <button
                            type="button"
                            @click="confirmarCierreOperativo"
                            class="px-3.5 py-1.5 rounded-lg text-white bg-[var(--maya-primary)] hover:brightness-110 font-semibold shadow-xs transition-all"
                        >
                            Confirmar Cierre Diario
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
