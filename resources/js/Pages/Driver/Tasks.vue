<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DriverLayout from '@/Layouts/DriverLayout.vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    },
});

// Search and filter state
const searchQuery = ref('');
const activeFilter = ref('TODOS'); // 'TODOS', 'PENDIENTES', 'EN_CURSO', 'ENTREGADOS', 'PAUSA'

// Bottom Sheet & Modal state
const selectedItem = ref(null);
const showBottomSheet = ref(false);
const showCommentModal = ref(false);
const showReportModal = ref(false);
const showMapModal = ref(false);
const mapTarget = ref(null);

// Form models
const newComment = ref('');
const reportReason = ref('');
const isSubmitting = ref(false);

// Filtered tasks
const filteredItems = computed(() => {
    let result = props.items;

    // Filter by tab
    if (activeFilter.value === 'PENDIENTES') {
        result = result.filter(i => i.status.codigo === 'PENDIENTE');
    } else if (activeFilter.value === 'EN_CURSO') {
        result = result.filter(i => i.status.codigo === 'EN_CURSO');
    } else if (activeFilter.value === 'ENTREGADOS') {
        result = result.filter(i => i.status.codigo === 'ENTREGADO' || i.status.codigo === 'COMPLETADA');
    } else if (activeFilter.value === 'PAUSA') {
        result = result.filter(i => i.status.codigo === 'PAUSA' || i.status.codigo === 'DEVUELTO');
    }

    // Filter by search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(i =>
            i.recipient_name?.toLowerCase().includes(query) ||
            i.destination_address?.toLowerCase().includes(query) ||
            i.tracking_number?.toLowerCase().includes(query)
        );
    }

    return result;
});

// Card interaction: Open bottom sheet
const openCardActions = (item) => {
    selectedItem.value = item;
    showBottomSheet.value = true;
};

const closeBottomSheet = () => {
    showBottomSheet.value = false;
};

// Map trigger
const triggerMap = (item, event) => {
    if (event) event.stopPropagation();
    mapTarget.value = item;
    showMapModal.value = true;
};

const openExternalMap = (type) => {
    if (!mapTarget.value) return;

    const address = mapTarget.value.destination_address || '';
    const coords = mapTarget.value.destination_coords;

    let targetLocation = address;
    if (coords && coords.lat && coords.lng) {
        targetLocation = `${coords.lat},${coords.lng}`;
    }

    let url = '';
    if (type === 'waze') {
        url = `https://waze.com/ul?q=${encodeURIComponent(targetLocation)}&navigate=yes`;
    } else {
        url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(targetLocation)}`;
    }

    window.open(url, '_blank');
    showMapModal.value = false;
};

// Bottom sheet actions
const handleComplete = () => {
    if (!selectedItem.value) return;
    closeBottomSheet();
    router.visit(route('driver.tasks.complete', selectedItem.value.id));
};

const handlePause = () => {
    if (!selectedItem.value) return;
    const item = selectedItem.value;
    closeBottomSheet();

    router.post(route('driver.tasks.status', item.id), {
        status: 'PAUSA',
    }, {
        preserveScroll: true,
    });
};

const handleStartRoute = (item) => {
    router.post(route('driver.tasks.status', item.id), {
        status: 'EN_CURSO',
    }, {
        preserveScroll: true,
    });
};

const openComments = () => {
    closeBottomSheet();
    newComment.value = '';
    showCommentModal.value = true;
};

const saveComment = () => {
    if (!selectedItem.value || !newComment.value.trim()) return;
    isSubmitting.value = true;

    router.post(route('driver.tasks.comment', selectedItem.value.id), {
        comment: newComment.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCommentModal.value = false;
            newComment.value = '';
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};

const openReport = () => {
    closeBottomSheet();
    reportReason.value = 'Cliente no responde en dirección';
    showReportModal.value = true;
};

const saveReport = () => {
    if (!selectedItem.value) return;
    isSubmitting.value = true;

    router.post(route('driver.tasks.status', selectedItem.value.id), {
        status: 'DEVUELTO',
        reason: reportReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showReportModal.value = false;
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Paradas - ${task.title}`" />

    <DriverLayout>
        <div class="space-y-4">
            <!-- Header Navigation & Plan Info -->
            <div class="flex items-center justify-between gap-2">
                <Link
                    :href="route('driver.dashboard')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors py-1 px-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <font-awesome-icon :icon="['fas', 'chevron-left']" class="text-[10px]" />
                    <span>Volver a Planes</span>
                </Link>

                <div class="text-right">
                    <span class="font-mono text-xs font-bold text-slate-800 dark:text-slate-200">
                        {{ task.title }}
                    </span>
                    <p class="text-[10px] text-slate-400 font-medium">
                        {{ task.completed_stops }} de {{ task.total_stops }} completadas
                    </p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar cliente, dirección o guía..."
                    class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 shadow-sm transition-all"
                />
                <font-awesome-icon
                    :icon="['fas', 'magnifying-glass']"
                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"
                />
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-[11px] font-semibold">
                <button
                    @click="activeFilter = 'TODOS'"
                    type="button"
                    class="px-3 py-1.5 rounded-full whitespace-nowrap transition-colors"
                    :class="activeFilter === 'TODOS'
                        ? 'bg-slate-900 text-white dark:bg-indigo-600 dark:text-white shadow-sm'
                        : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80'"
                >
                    Todas ({{ items.length }})
                </button>
                <button
                    @click="activeFilter = 'PENDIENTES'"
                    type="button"
                    class="px-3 py-1.5 rounded-full whitespace-nowrap transition-colors"
                    :class="activeFilter === 'PENDIENTES'
                        ? 'bg-amber-100 text-amber-900 border border-amber-300 shadow-sm font-bold'
                        : 'bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 border border-slate-200/80 dark:border-slate-700/80'"
                >
                    Pendientes
                </button>
                <button
                    @click="activeFilter = 'EN_CURSO'"
                    type="button"
                    class="px-3 py-1.5 rounded-full whitespace-nowrap transition-colors"
                    :class="activeFilter === 'EN_CURSO'
                        ? 'bg-sky-100 text-sky-900 border border-sky-300 shadow-sm font-bold'
                        : 'bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-300 border border-slate-200/80 dark:border-slate-700/80'"
                >
                    En Curso
                </button>
                <button
                    @click="activeFilter = 'ENTREGADOS'"
                    type="button"
                    class="px-3 py-1.5 rounded-full whitespace-nowrap transition-colors"
                    :class="activeFilter === 'ENTREGADOS'
                        ? 'bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-sm font-bold'
                        : 'bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-300 border border-slate-200/80 dark:border-slate-700/80'"
                >
                    Entregadas
                </button>
                <button
                    @click="activeFilter = 'PAUSA'"
                    type="button"
                    class="px-3 py-1.5 rounded-full whitespace-nowrap transition-colors"
                    :class="activeFilter === 'PAUSA'
                        ? 'bg-purple-100 text-purple-900 border border-purple-300 shadow-sm font-bold'
                        : 'bg-white dark:bg-slate-800 text-purple-700 dark:text-purple-300 border border-slate-200/80 dark:border-slate-700/80'"
                >
                    Pausadas / Reportes
                </button>
            </div>

            <!-- Stops / Tasks Cards List -->
            <div v-if="filteredItems.length > 0" class="space-y-3">
                <div
                    v-for="item in filteredItems"
                    :key="item.id"
                    @click="openCardActions(item)"
                    class="relative p-4 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200/90 dark:border-slate-700/70 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-600 cursor-pointer active:scale-[0.99] transition-all overflow-hidden"
                >
                    <!-- Stop Order Index Tag & Pastel Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-slate-900 text-white dark:bg-slate-700 font-black text-[11px] flex items-center justify-center shadow-xs">
                                #{{ item.stop_order }}
                            </span>

                            <!-- Pastel Status Badge -->
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase shadow-xs transition-colors"
                                :class="item.status.badge"
                            >
                                {{ item.status.valor }}
                            </span>

                            <span v-if="item.priority.codigo === 'ALTA'" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                URGENTE
                            </span>
                        </div>

                        <!-- Corner Map Icon Trigger (Waze / Google Maps) -->
                        <button
                            @click.stop="triggerMap(item, $event)"
                            type="button"
                            class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-700/80 hover:bg-sky-50 dark:hover:bg-sky-950/60 text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 border border-slate-200/70 dark:border-slate-600/60 flex items-center justify-center transition-all shadow-xs"
                            title="Abrir en Waze o Google Maps"
                        >
                            <font-awesome-icon :icon="['fas', 'location-dot']" class="text-xs" />
                        </button>
                    </div>

                    <!-- Client Info -->
                    <div class="space-y-1 mb-2.5">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-snug">
                            {{ item.recipient_name }}
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <a
                                v-if="item.recipient_phone"
                                :href="`tel:${item.recipient_phone}`"
                                @click.stop
                                class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-medium hover:underline text-[11px]"
                            >
                                <font-awesome-icon :icon="['fas', 'phone']" class="text-[9px]" />
                                {{ item.recipient_phone }}
                            </a>
                            <span v-if="item.recipient_phone" class="text-slate-300">•</span>
                            <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                {{ item.tracking_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Destination Address -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 flex items-start gap-2">
                        <font-awesome-icon :icon="['fas', 'map-pin']" class="text-slate-400 text-xs mt-0.5 flex-shrink-0" />
                        <span class="leading-relaxed line-clamp-2 text-[11px]">
                            {{ item.destination_address }}
                        </span>
                    </div>

                    <!-- Package Info & Notes Preview -->
                    <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                        <div class="flex items-center gap-1.5">
                            <font-awesome-icon :icon="['fas', 'box']" class="text-[10px]" />
                            <span>{{ item.package_type }} <template v-if="item.weight_kg">({{ item.weight_kg }} kg)</template></span>
                        </div>

                        <div v-if="item.notes" class="flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium">
                            <font-awesome-icon :icon="['fas', 'comment']" class="text-[10px]" />
                            <span>Comentarios</span>
                        </div>
                        <div v-else class="text-indigo-600 dark:text-indigo-400 font-medium flex items-center gap-1">
                            <span>Toca para opciones</span>
                            <font-awesome-icon :icon="['fas', 'chevron-right']" class="text-[9px]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty Filter State -->
            <div v-else class="text-center py-12 px-4 bg-white dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                <p class="text-xs text-slate-400">
                    No se encontraron entregas con el criterio seleccionado.
                </p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- BOTTOM SHEET DRAWER (Sliding Up from Bottom) -->
        <!-- ========================================================================= -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showBottomSheet"
                @click="closeBottomSheet"
                class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-end justify-center"
            >
                <div
                    @click.stop
                    class="w-full max-w-md bg-white dark:bg-slate-900 rounded-t-3xl shadow-2xl p-5 border-t border-slate-200 dark:border-slate-800 animate-in slide-in-from-bottom duration-300 space-y-4"
                >
                    <!-- Grabber handle -->
                    <div class="w-12 h-1.5 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto -mt-1"></div>

                    <!-- Stop Summary Header -->
                    <div v-if="selectedItem" class="border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Parada #{{ selectedItem.stop_order }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase" :class="selectedItem.status.badge">
                                {{ selectedItem.status.valor }}
                            </span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white mt-0.5 truncate">
                            {{ selectedItem.recipient_name }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ selectedItem.destination_address }}
                        </p>
                    </div>

                    <!-- Action Options List (COMPLETAR, PAUSAR, REPORTAR, COMENTARIOS, CERRAR) -->
                    <div class="space-y-2">
                        <!-- COMPLETAR -->
                        <button
                            @click="handleComplete"
                            type="button"
                            class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-sm transition-all"
                        >
                            <font-awesome-icon :icon="['fas', 'circle-check']" class="text-base" />
                            <span>COMPLETAR ENTREGA</span>
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <!-- PAUSAR -->
                            <button
                                @click="handlePause"
                                type="button"
                                class="py-3 px-3 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 font-semibold text-xs flex items-center justify-center gap-1.5 hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors"
                            >
                                <font-awesome-icon :icon="['fas', 'clock']" />
                                <span>PAUSAR</span>
                            </button>

                            <!-- REPORTAR -->
                            <button
                                @click="openReport"
                                type="button"
                                class="py-3 px-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 font-semibold text-xs flex items-center justify-center gap-1.5 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-colors"
                            >
                                <font-awesome-icon :icon="['fas', 'triangle-exclamation']" />
                                <span>REPORTAR</span>
                            </button>
                        </div>

                        <!-- COMENTARIOS -->
                        <button
                            @click="openComments"
                            type="button"
                            class="w-full py-3 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-xs flex items-center justify-center gap-2 transition-colors"
                        >
                            <font-awesome-icon :icon="['fas', 'comment']" />
                            <span>COMENTARIOS</span>
                        </button>

                        <!-- CERRAR -->
                        <button
                            @click="closeBottomSheet"
                            type="button"
                            class="w-full py-2.5 px-4 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 font-semibold text-xs transition-colors"
                        >
                            CERRAR
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================================================= -->
        <!-- MODAL COMENTARIOS -->
        <!-- ========================================================================= -->
        <div
            v-if="showCommentModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-4.5 border border-slate-200 dark:border-slate-800 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <font-awesome-icon :icon="['fas', 'comment']" class="text-indigo-600 dark:text-indigo-400" />
                        Comentarios de la Parada
                    </h3>
                    <button
                        @click="showCommentModal = false"
                        type="button"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <!-- Historial de notas -->
                <div v-if="selectedItem?.notes" class="max-h-36 overflow-y-auto p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-[11px] text-slate-600 dark:text-slate-300 whitespace-pre-line border border-slate-100 dark:border-slate-700">
                    {{ selectedItem.notes }}
                </div>
                <div v-else class="text-[11px] text-slate-400 italic">
                    Sin observaciones previas.
                </div>

                <!-- Input nuevo comentario -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Agregar observación:
                    </label>
                    <textarea
                        v-model="newComment"
                        rows="3"
                        placeholder="Ej: Portón verde cerrado, cliente pidió esperar 5 min..."
                        class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    ></textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button
                        @click="showCommentModal = false"
                        type="button"
                        class="w-1/2 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveComment"
                        :disabled="!newComment.trim() || isSubmitting"
                        type="button"
                        class="w-1/2 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs shadow-sm"
                    >
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL REPORTAR INCIDENCIA / DEVOLUCIÓN -->
        <!-- ========================================================================= -->
        <div
            v-if="showReportModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-4.5 border border-slate-200 dark:border-slate-800 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                        <font-awesome-icon :icon="['fas', 'triangle-exclamation']" />
                        Reportar Devolución / Problema
                    </h3>
                    <button
                        @click="showReportModal = false"
                        type="button"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Motivo del reporte:
                    </label>
                    <select
                        v-model="reportReason"
                        class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-rose-500/40"
                    >
                        <option value="Cliente no responde en dirección">Cliente ausente / no responde</option>
                        <option value="Dirección incorrecta o no localizada">Dirección incorrecta o incompleta</option>
                        <option value="Cliente rechazó el paquete">Cliente rechazó el paquete</option>
                        <option value="Zona inaccesible o peligrosa">Zona inaccesible o peligrosa</option>
                        <option value="Paquete con daño externo">Paquete con anomalía o daño</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button
                        @click="showReportModal = false"
                        type="button"
                        class="w-1/2 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveReport"
                        :disabled="isSubmitting"
                        type="button"
                        class="w-1/2 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-sm"
                    >
                        Confirmar Devolución
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL NAVEGACIÓN MAPA (WAZE O GOOGLE MAPS) -->
        <!-- ========================================================================= -->
        <div
            v-if="showMapModal"
            @click="showMapModal = false"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div
                @click.stop
                class="w-full max-w-xs bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-4.5 border border-slate-200 dark:border-slate-800 space-y-3"
            >
                <div class="text-center">
                    <div class="w-10 h-10 mx-auto rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm mb-2">
                        <font-awesome-icon :icon="['fas', 'location-dot']" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Abrir Navegación
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                        {{ mapTarget?.recipient_name }}
                    </p>
                </div>

                <div class="space-y-2 pt-1">
                    <button
                        @click="openExternalMap('waze')"
                        type="button"
                        class="w-full py-2.5 px-4 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-colors"
                    >
                        <span>Abrir en Waze</span>
                    </button>

                    <button
                        @click="openExternalMap('google')"
                        type="button"
                        class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-colors"
                    >
                        <span>Abrir en Google Maps</span>
                    </button>

                    <button
                        @click="showMapModal = false"
                        type="button"
                        class="w-full py-2 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-medium"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </DriverLayout>
</template>
