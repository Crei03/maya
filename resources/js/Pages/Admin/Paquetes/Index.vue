<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import ColumnVisibilitySelector from '@/Components/buttons/ColumnVisibilitySelector.vue';
import Excel from '@/Components/buttons/Excel.vue';
import Modal from '@/Components/Modal.vue';

// --- Estado general ---
const loading = ref(false);
const shipments = ref([]);
const pagination = ref(null);
const perPage = ref(15);
const successMessage = ref('');
const errorMessage = ref('');

// --- Catálogos auxiliares ---
const clientsList = ref([]);
const warehousesList = ref([]);

// --- Columnas y preferencias ---
const columns = [
    { key: 'tracking_number', label: 'Tracking' },
    { key: 'status', label: 'Estado' },
    { key: 'recipient_name', label: 'Cliente' },
    { key: 'destination_address', label: 'Destino' },
    { key: 'warehouse_name', label: 'Bodega' },
    { key: 'package_type', label: 'Tipo' },
    { key: 'weight_lb', label: 'Peso (lbs)' },
    { key: 'task_title', label: 'Tarea / Ruta' },
    { key: 'created_at', label: 'Registrado' },
    { key: 'actions', label: 'Acciones' },
];

const defaultVisibleColumns = columns.map((c) => c.key);
const visibleColumns = ref([...defaultVisibleColumns]);
const savingColumnPreference = ref(false);

// --- Filtros ---
const showFilters = ref(false);
const filters = reactive({
    search: '',
    status: '',
    warehouse_id: '',
    package_type: '',
    date_from: '',
    date_to: '',
});

// --- Modal de Crear / Editar ---
const modalOpen = ref(false);
const editingId = ref(null);
const saving = ref(false);
const formErrors = ref({});

const form = reactive({
    sender_id: '',
    warehouse_id: '',
    destination_address: '',
    destination_coords: '',
    package_type: 'caja',
    weight_lb: '',
    weight_kg: '',
    total_cost: '',
    content_description: '',
    dimensions: '',
    status: 'pending',
});

// --- Búsqueda predictiva de Clientes / Directorio ---
const clientSearchQuery = ref('');
const clientSearchResults = ref([]);
const isSearchingClients = ref(false);
const selectedClient = ref(null);
const showClientDropdown = ref(false);

const searchClients = async () => {
    if (!clientSearchQuery.value || clientSearchQuery.value.trim().length < 1) {
        clientSearchResults.value = clientsList.value.slice(0, 10);
        showClientDropdown.value = clientSearchResults.value.length > 0;
        return;
    }
    isSearchingClients.value = true;
    try {
        const res = await window.axios.get(route('admin.clients.search'), {
            params: {
                q: clientSearchQuery.value.trim(),
            },
        });
        clientSearchResults.value = res.data?.data || [];
        showClientDropdown.value = true;
    } catch {
        clientSearchResults.value = [];
    } finally {
        isSearchingClients.value = false;
    }
};

const selectClient = (client) => {
    selectedClient.value = client;
    form.sender_id = client.id;
    const addr = client.direccion || [client.calle, client.street_name, client.street_number].filter(Boolean).join(' ');
    const refPoint = client.reference_point ? ` (Ref: ${client.reference_point})` : '';
    if (addr) {
        form.destination_address = addr + refPoint;
    }
    if (client.destination_coords) {
        form.destination_coords = typeof client.destination_coords === 'object' ? JSON.stringify(client.destination_coords) : client.destination_coords;
    }
    showClientDropdown.value = false;
    clientSearchQuery.value = client.full_name || `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.email || '';
};

const clearClient = () => {
    selectedClient.value = null;
    form.sender_id = '';
    clientSearchQuery.value = '';
    clientSearchResults.value = [];
    showClientDropdown.value = false;
};

// --- Modal de Detalle / Timeline ---
const detailOpen = ref(false);
const detailShipment = ref(null);
const loadingDetail = ref(false);

// --- Métodos de Carga ---
const fetchShipments = async (page = 1) => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const params = {
            page,
            per_page: perPage.value,
            ...filters,
        };
        const response = await window.axios.get(route('admin.shipments.list'), { params });
        if (response.data.success) {
            shipments.value = response.data.data.data;
            pagination.value = response.data.data.meta;
        }
    } catch (err) {
        errorMessage.value = 'Error al cargar los envíos.';
    } finally {
        loading.value = false;
    }
};

const fetchCatalogs = async () => {
    try {
        const [clientsRes, warehousesRes] = await Promise.all([
            window.axios.get(route('admin.clients.list'), { params: { per_page: 200 } }),
            window.axios.get(route('admin.bodegas.list'), { params: { per_page: 100, is_active: true } }),
        ]);

        if (clientsRes.data.success) {
            clientsList.value = clientsRes.data.data.data || clientsRes.data.data;
        }
        if (warehousesRes.data.success) {
            warehousesList.value = warehousesRes.data.data.data || warehousesRes.data.data;
        }
    } catch (e) {
        console.warn('Error cargando catálogos', e);
    }
};

const fetchColumnPreferences = async () => {
    try {
        const res = await window.axios.get(route('admin.column-preferences.show', { module: 'paquetes' }));
        if (res.data.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
            visibleColumns.value = res.data.data;
        }
    } catch {
        // Fallback a columnas por defecto
        visibleColumns.value = [...defaultVisibleColumns];
    }
};

const saveColumnPreference = async (newColumns) => {
    savingColumnPreference.value = true;
    try {
        await window.axios.put(route('admin.column-preferences.update', { module: 'paquetes' }), {
            visible_columns: newColumns,
        });
        visibleColumns.value = [...newColumns];
        successMessage.value = 'Preferencias de columnas guardadas.';
    } catch {
        errorMessage.value = 'No fue posible guardar las preferencias de columnas.';
    } finally {
        savingColumnPreference.value = false;
    }
};

const fetchAllShipmentsForExport = async () => {
    const params = {
        per_page: 1000,
        ...filters,
    };
    const response = await window.axios.get(route('admin.shipments.list'), { params });
    return response.data?.data?.data || [];
};

// --- Manejo del Formulario (Crear / Editar) ---
const resetForm = () => {
    Object.assign(form, {
        sender_id: '',
        warehouse_id: warehousesList.value[0]?.id || '',
        destination_address: '',
        destination_coords: '',
        package_type: 'caja',
        weight_lb: '',
        weight_kg: '',
        total_cost: '',
        content_description: '',
        dimensions: '',
        status: 'pending',
    });
    editingId.value = null;
    formErrors.value = {};
    clearClient();
};

const openCreateModal = () => {
    successMessage.value = '';
    errorMessage.value = '';
    resetForm();
    modalOpen.value = true;
};

const openEditModal = (shipment) => {
    successMessage.value = '';
    errorMessage.value = '';
    editingId.value = shipment.id;
    formErrors.value = {};

    Object.assign(form, {
        sender_id: shipment.sender_id || shipment.sender?.id || '',
        warehouse_id: shipment.warehouse_id || shipment.warehouse?.id || '',
        destination_address: shipment.destination_address || '',
        destination_coords: shipment.destination_coords ? (typeof shipment.destination_coords === 'object' ? JSON.stringify(shipment.destination_coords) : shipment.destination_coords) : '',
        package_type: shipment.package_type || 'caja',
        weight_lb: shipment.weight_lb || '',
        weight_kg: shipment.weight_kg || '',
        total_cost: shipment.total_cost || '',
        content_description: shipment.content_description || '',
        dimensions: shipment.dimensions ? (typeof shipment.dimensions === 'object' ? JSON.stringify(shipment.dimensions) : shipment.dimensions) : '',
        status: shipment.status || 'pending',
    });

    if (shipment.sender || shipment.client) {
        const c = shipment.sender || shipment.client;
        selectedClient.value = c;
        clientSearchQuery.value = c.full_name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || c.email || '';
    } else if (shipment.sender_id) {
        const found = clientsList.value.find(c => c.id === shipment.sender_id);
        if (found) {
            selectedClient.value = found;
            clientSearchQuery.value = found.full_name || `${found.first_name || ''} ${found.last_name || ''}`.trim() || found.email || '';
        } else {
            clearClient();
        }
    } else {
        clearClient();
    }

    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    resetForm();
};

const onWeightLbChange = () => {
    const lb = parseFloat(form.weight_lb);
    if (!isNaN(lb) && lb > 0) {
        form.weight_kg = (lb / 2.20462).toFixed(2);
    }
};

const saveShipment = async () => {
    saving.value = true;
    formErrors.value = {};
    errorMessage.value = '';

    const payload = {
        sender_id: form.sender_id,
        warehouse_id: form.warehouse_id,
        destination_address: form.destination_address,
        destination_coords: form.destination_coords || null,
        package_type: form.package_type,
        weight_lb: parseFloat(form.weight_lb) || 0,
        weight_kg: form.weight_kg ? parseFloat(form.weight_kg) : null,
        total_cost: form.total_cost ? parseFloat(form.total_cost) : null,
        content_description: form.content_description || null,
        dimensions: form.dimensions || null,
        ...(editingId.value ? { status: form.status } : {}),
    };

    try {
        if (editingId.value) {
            await window.axios.patch(route('admin.shipments.update', { id: editingId.value }), payload);
            successMessage.value = 'Paquete actualizado exitosamente.';
        } else {
            await window.axios.post(route('admin.shipments.store'), payload);
            successMessage.value = 'Paquete creado exitosamente.';
        }
        closeModal();
        await fetchShipments(pagination.value?.current_page || 1);
    } catch (err) {
        if (err?.response?.status === 422) {
            formErrors.value = err.response.data.errors || {};
        } else {
            errorMessage.value = err?.response?.data?.message || 'Error al guardar el paquete.';
        }
    } finally {
        saving.value = false;
    }
};

const deleteShipment = async (shipment) => {
    if (!confirm(`¿Estás seguro de eliminar el paquete con tracking ${shipment.tracking_number}?`)) {
        return;
    }

    try {
        const res = await window.axios.delete(route('admin.shipments.destroy', { id: shipment.id }));
        if (res.data.success) {
            successMessage.value = res.data.message || 'Paquete eliminado exitosamente.';
            await fetchShipments(pagination.value?.current_page || 1);
        }
    } catch (err) {
        alert(err?.response?.data?.message || 'No fue posible eliminar el paquete (puede tener relaciones activas).');
    }
};

// --- Modal de Detalle / Timeline ---
const openDetailModal = async (shipment) => {
    detailShipment.value = null;
    detailOpen.value = true;
    loadingDetail.value = true;

    try {
        const res = await window.axios.get(route('admin.shipments.show', { id: shipment.id }));
        if (res.data.success) {
            detailShipment.value = res.data.data;
        }
    } catch {
        errorMessage.value = 'Error al consultar el detalle del paquete.';
    } finally {
        loadingDetail.value = false;
    }
};

const closeDetailModal = () => {
    detailOpen.value = false;
};

// Formato de fechas
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-PA', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

onMounted(async () => {
    await fetchCatalogs();
    await fetchColumnPreferences();
    await fetchShipments(1);
});
</script>

<template>
    <Head title="Gestión de Envíos" />

    <AdminLayout title="Envíos">
        <div class="space-y-6">
            <!-- Header y Acciones principales -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                                <font-awesome-icon :icon="['fas', 'box']" class="text-lg" />
                            </span>
                            <h1 class="text-xl font-bold text-[var(--maya-text-main)]">Gestión de Envíos y Paquetes</h1>
                        </div>
                        <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                            Inventario maestro de paquetes, historial de tracking y trazabilidad de entregas.
                        </p>
                    </div>

                    <!-- Toolbar derecha -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Excel
                            :columns="columns"
                            :fetch-all-data="fetchAllShipmentsForExport"
                            module-name="paquetes"
                            :loading="loading"
                            variant="success"
                        />

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                            @click="showFilters = !showFilters"
                        >
                            <font-awesome-icon :icon="['fas', 'filter']" />
                            {{ showFilters ? 'Ocultar filtros' : 'Filtros' }}
                        </button>

                        <ColumnVisibilitySelector
                            :columns="columns"
                            :model-value="visibleColumns"
                            :loading="savingColumnPreference"
                            @save="saveColumnPreference"
                        />

                        <RefreshButton :loading="loading" @refresh="fetchShipments(1)" />

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[var(--maya-primary-dark)]"
                            @click="openCreateModal"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            Nuevo Envío
                        </button>
                    </div>
                </div>

                <!-- Mensajes -->
                <div v-if="successMessage" class="mt-4 rounded-xl border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] p-3 text-sm text-[var(--maya-success-dark)]">
                    {{ successMessage }}
                </div>
                <div v-if="errorMessage" class="mt-4 rounded-xl border border-[var(--maya-danger)] bg-[var(--maya-danger-alpha)] p-3 text-sm text-[var(--maya-danger)]">
                    {{ errorMessage }}
                </div>

                <!-- Panel desplegable de Filtros -->
                <div v-if="showFilters" class="mt-5 grid grid-cols-1 gap-3 border-t border-[var(--maya-border)] pt-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Buscar</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Tracking o remitente..."
                            class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            @keyup.enter="fetchShipments(1)"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Estado</label>
                        <select
                            v-model="filters.status"
                            class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            @change="fetchShipments(1)"
                        >
                            <option value="">Todos los estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="in_warehouse">En bodega</option>
                            <option value="assigned">Asignado a ruta</option>
                            <option value="in_transit">En tránsito</option>
                            <option value="delivered">Entregado</option>
                            <option value="returned">Devuelto</option>
                            <option value="failed">Fallido</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Bodega</label>
                        <select
                            v-model="filters.warehouse_id"
                            class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            @change="fetchShipments(1)"
                        >
                            <option value="">Todas las bodegas</option>
                            <option v-for="wh in warehousesList" :key="wh.id" :value="wh.id">
                                {{ wh.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Tipo</label>
                            <select
                                v-model="filters.package_type"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                @change="fetchShipments(1)"
                            >
                                <option value="">Todos</option>
                                <option value="caja">Caja</option>
                                <option value="sobre">Sobre</option>
                                <option value="paquete">Paquete</option>
                                <option value="palet">Palet</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:opacity-80"
                            @click="() => { Object.keys(filters).forEach(k => filters[k] = ''); fetchShipments(1); }"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </section>

            <!-- Tabla Principal de Paquetes -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="shipments"
                    :visible-columns="visibleColumns"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    empty-text="No se encontraron paquetes registrados."
                    @change-page="fetchShipments"
                >
                    <!-- Tracking Number -->
                    <template #cell-tracking_number="{ row }">
                        <button
                            type="button"
                            class="font-mono text-xs font-bold text-[var(--maya-primary)] hover:underline"
                            title="Ver detalles"
                            @click="openDetailModal(row)"
                        >
                            {{ row.tracking_number }}
                        </button>
                    </template>

                    <!-- Estado con Badge -->
                    <template #cell-status="{ row }">
                        <span
                            v-if="row.status === 'delivered'"
                            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300"
                        >
                            <font-awesome-icon :icon="['fas', 'check']" class="text-[10px]" />
                            Entregado
                        </span>
                        <span
                            v-else-if="row.status === 'in_transit'"
                            class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                        >
                            <font-awesome-icon :icon="['fas', 'truck']" class="text-[10px]" />
                            En tránsito
                        </span>
                        <span
                            v-else-if="row.status === 'assigned'"
                            class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
                        >
                            <font-awesome-icon :icon="['fas', 'route']" class="text-[10px]" />
                            Asignado
                        </span>
                        <span
                            v-else-if="row.status === 'in_warehouse'"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300"
                        >
                            <font-awesome-icon :icon="['fas', 'warehouse']" class="text-[10px]" />
                            En bodega
                        </span>
                        <span
                            v-else-if="row.status === 'returned'"
                            class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-800 dark:bg-orange-900/30 dark:text-orange-300"
                        >
                            <font-awesome-icon :icon="['fas', 'rotate-left']" class="text-[10px]" />
                            Devuelto
                        </span>
                        <span
                            v-else-if="row.status === 'failed'"
                            class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300"
                        >
                            <font-awesome-icon :icon="['fas', 'xmark']" class="text-[10px]" />
                            Fallido
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            Pendiente
                        </span>
                    </template>

                    <!-- Remitente / Cliente -->
                    <template #cell-recipient_name="{ row }">
                        <span class="font-medium text-[var(--maya-text-main)]">
                            {{ row.recipient_name || row.sender?.full_name || 'Sin cliente' }}
                        </span>
                    </template>

                    <!-- Destino -->
                    <template #cell-destination_address="{ row }">
                        <span class="text-xs text-[var(--maya-text-muted)] line-clamp-1" :title="row.destination_address">
                            📍 {{ row.destination_address }}
                        </span>
                    </template>

                    <!-- Bodega -->
                    <template #cell-warehouse_name="{ row }">
                        <span class="text-xs text-[var(--maya-text-main)]">
                            {{ row.warehouse_name || row.warehouse?.name || '-' }}
                        </span>
                    </template>

                    <!-- Peso en libras -->
                    <template #cell-weight_lb="{ row }">
                        <span class="font-mono text-xs font-semibold">{{ row.weight_lb }} lbs</span>
                    </template>

                    <!-- Tarea / Ruta -->
                    <template #cell-task_title="{ row }">
                        <span v-if="row.task_title || row.assigned_task?.title" class="inline-flex items-center gap-1 font-mono text-xs font-bold text-[var(--maya-primary)]">
                            <font-awesome-icon :icon="['fas', 'route']" class="text-[10px]" />
                            {{ row.task_title || row.assigned_task?.title }}
                        </span>
                        <span v-else class="text-xs text-[var(--maya-text-muted)]">Sin asignar</span>
                    </template>

                    <!-- Fecha de Creación -->
                    <template #cell-created_at="{ row }">
                        <span class="text-xs text-[var(--maya-text-muted)]">
                            {{ formatDate(row.created_at) }}
                        </span>
                    </template>

                    <!-- Acciones -->
                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] text-xs text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                                title="Ver Detalle y Tracking"
                                @click="openDetailModal(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'eye']" />
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] text-xs text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                                title="Editar Paquete"
                                @click="openEditModal(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'pencil']" />
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 text-xs text-red-600 hover:bg-red-50 dark:border-red-900/30 dark:hover:bg-red-950/20"
                                title="Eliminar Paquete"
                                @click="deleteShipment(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'trash']" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </section>
        </div>

        <!-- ==================================================================== -->
        <!-- MODAL: CREAR / EDITAR ENVÍO                                          -->
        <!-- ==================================================================== -->
        <Modal :show="modalOpen" max-width="2xl" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-[var(--maya-text-main)]">
                            {{ editingId ? 'Editar Paquete' : 'Registrar Nuevo Envío' }}
                        </h2>
                        <p class="text-xs text-[var(--maya-text-muted)]">
                            Ingresa los datos del paquete para su almacenaje y despacho.
                        </p>
                    </div>
                    <button type="button" class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]" @click="closeModal">
                        <font-awesome-icon :icon="['fas', 'xmark']" class="text-lg" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Bodega de Origen -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Bodega de Origen *
                            </label>
                            <select
                                v-model="form.warehouse_id"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            >
                                <option value="">Selecciona la bodega</option>
                                <option v-for="wh in warehousesList" :key="wh.id" :value="wh.id">
                                    {{ wh.name }} ({{ wh.code }})
                                </option>
                            </select>
                            <p v-if="formErrors.warehouse_id" class="mt-1 text-xs text-red-500">{{ formErrors.warehouse_id[0] || formErrors.warehouse_id }}</p>
                        </div>

                        <!-- Cliente / Destinatario (Directorio Unificado) -->
                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Cliente / Destinatario *
                                </label>
                                <span class="text-[11px] text-[var(--maya-text-muted)]">Busca en el directorio para autocompletar</span>
                            </div>

                            <div class="relative mt-1">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input
                                            v-model="clientSearchQuery"
                                            type="text"
                                            placeholder="Buscar cliente por nombre, teléfono, email..."
                                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] pl-8 pr-8 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                            @input="searchClients"
                                            @focus="searchClients"
                                        />
                                        <font-awesome-icon :icon="['fas', 'user']" class="absolute left-2.5 top-2.5 text-xs text-[var(--maya-text-muted)]" />
                                        <button
                                            v-if="clientSearchQuery"
                                            type="button"
                                            class="absolute right-2.5 top-2 text-xs text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                                            @click="clearClient"
                                        >
                                            <font-awesome-icon :icon="['fas', 'xmark']" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Dropdown con resultados predictivos de clientes -->
                                <div
                                    v-if="showClientDropdown && clientSearchResults.length"
                                    class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] shadow-lg"
                                >
                                    <div
                                        v-for="c in clientSearchResults"
                                        :key="c.id"
                                        class="cursor-pointer border-b border-[var(--maya-border)] p-2.5 text-xs hover:bg-[var(--maya-hover-surface)] transition-colors last:border-b-0"
                                        @click="selectClient(c)"
                                    >
                                        <div class="flex items-center justify-between font-semibold text-[var(--maya-text-main)]">
                                            <span>{{ c.full_name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || c.email }}</span>
                                            <span class="font-mono text-[11px] text-[var(--maya-primary)]">{{ c.phone }}</span>
                                        </div>
                                        <p v-if="c.direccion || c.street_name" class="text-[11px] text-[var(--maya-text-muted)] truncate mt-0.5">
                                            📍 {{ c.direccion || c.street_name }}
                                            <span v-if="c.reference_point"> (Ref: {{ c.reference_point }})</span>
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else-if="showClientDropdown && isSearchingClients"
                                    class="absolute z-20 mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-3 text-center text-xs text-[var(--maya-text-muted)] shadow-lg"
                                >
                                    Buscando clientes...
                                </div>
                            </div>

                            <!-- Card resumen de cliente seleccionado -->
                            <div v-if="selectedClient" class="mt-1.5 flex items-center justify-between rounded-lg bg-[var(--maya-primary-alpha)] border border-[var(--maya-primary)] px-2.5 py-1.5 text-xs text-[var(--maya-primary)]">
                                <div class="flex items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'check']" class="text-xs" />
                                    <span>
                                        Cliente: <strong>{{ selectedClient.full_name || `${selectedClient.first_name || ''} ${selectedClient.last_name || ''}`.trim() || selectedClient.email }}</strong>
                                        <span v-if="selectedClient.phone" class="font-mono ml-1">({{ selectedClient.phone }})</span>
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="text-[11px] font-semibold underline hover:opacity-80"
                                    @click="clearClient"
                                >
                                    Cambiar
                                </button>
                            </div>
                            <p v-if="formErrors.sender_id" class="mt-1 text-xs text-red-500">{{ formErrors.sender_id[0] || formErrors.sender_id }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Dirección de Entrega (Destino) *
                            </label>
                            <input
                                v-model="form.destination_address"
                                type="text"
                                placeholder="Calle, edificio, urbanización, corregimiento..."
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            />
                            <p v-if="formErrors.destination_address" class="mt-1 text-xs text-red-500">{{ formErrors.destination_address[0] || formErrors.destination_address }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Tipo de Paquete *
                            </label>
                            <select
                                v-model="form.package_type"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            >
                                <option value="caja">Caja</option>
                                <option value="sobre">Sobre</option>
                                <option value="paquete">Paquete</option>
                                <option value="palet">Palet</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Peso (lbs) *
                                </label>
                                <input
                                    v-model="form.weight_lb"
                                    type="number"
                                    step="0.1"
                                    placeholder="Libras"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 font-mono text-xs text-[var(--maya-text-main)] focus:outline-none"
                                    @input="onWeightLbChange"
                                />
                                <p v-if="formErrors.weight_lb" class="mt-1 text-xs text-red-500">{{ formErrors.weight_lb[0] || formErrors.weight_lb }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Equivalente kg
                                </label>
                                <input
                                    v-model="form.weight_kg"
                                    type="number"
                                    step="0.01"
                                    placeholder="Kg"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] px-3 py-2 font-mono text-xs text-[var(--maya-text-muted)] focus:outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Dimensiones (L x A x H cm)
                            </label>
                            <input
                                v-model="form.dimensions"
                                type="text"
                                placeholder="Ej: 30x20x15"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Costo del Envío ($)
                            </label>
                            <input
                                v-model="form.total_cost"
                                type="number"
                                step="0.01"
                                placeholder="Ej: 15.00"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 font-mono text-xs text-[var(--maya-text-main)] focus:outline-none"
                            />
                        </div>

                        <!-- Estado (solo en edición) -->
                        <div v-if="editingId">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Estado Actual
                            </label>
                            <select
                                v-model="form.status"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] focus:outline-none"
                            >
                                <option value="pending">Pendiente</option>
                                <option value="in_warehouse">En bodega</option>
                                <option value="assigned">Asignado</option>
                                <option value="in_transit">En tránsito</option>
                                <option value="delivered">Entregado</option>
                                <option value="returned">Devuelto</option>
                                <option value="failed">Fallido</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Descripción del Contenido
                            </label>
                            <input
                                v-model="form.content_description"
                                type="text"
                                placeholder="Descripción opcional de la mercancía"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-[var(--maya-border)] pt-4">
                        <button
                            type="button"
                            class="rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                            @click="closeModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] disabled:opacity-50"
                            :disabled="saving"
                            @click="saveShipment"
                        >
                            {{ saving ? 'Guardando...' : (editingId ? 'Actualizar Paquete' : 'Crear Paquete') }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ==================================================================== -->
        <!-- MODAL: DETALLE COMPLETO Y TIMELINE DE TRACKING                       -->
        <!-- ==================================================================== -->
        <Modal :show="detailOpen" max-width="3xl" @close="closeDetailModal">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-4">
                    <div v-if="detailShipment">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-bold text-[var(--maya-primary)]">{{ detailShipment.tracking_number }}</span>
                            <span class="rounded-full bg-[var(--maya-primary-alpha)] px-2.5 py-0.5 text-xs font-semibold text-[var(--maya-primary)]">
                                {{ detailShipment.status }}
                            </span>
                        </div>
                        <p class="text-xs text-[var(--maya-text-muted)]">
                            Registrado el {{ formatDate(detailShipment.created_at) }}
                        </p>
                    </div>
                    <button type="button" class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]" @click="closeDetailModal">
                        <font-awesome-icon :icon="['fas', 'xmark']" class="text-lg" />
                    </button>
                </div>

                <div v-if="loadingDetail" class="py-12 text-center text-sm text-[var(--maya-text-muted)]">
                    Cargando información del paquete...
                </div>

                <div v-else-if="detailShipment" class="mt-4 space-y-6">
                    <!-- Ficha resumen -->
                    <div class="grid grid-cols-2 gap-3 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-4 text-xs sm:grid-cols-4">
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Cliente:</span>
                            <p class="font-bold text-[var(--maya-text-main)]">{{ detailShipment.recipient_name || detailShipment.sender?.full_name || 'N/A' }}</p>
                            <p v-if="detailShipment.recipient_phone" class="text-[11px] text-[var(--maya-text-muted)]">📞 {{ detailShipment.recipient_phone }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Bodega Origen:</span>
                            <p class="font-bold text-[var(--maya-text-main)]">{{ detailShipment.warehouse_name || detailShipment.warehouse?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Peso / Tipo:</span>
                            <p class="font-mono font-bold text-[var(--maya-text-main)]">{{ detailShipment.weight_lb }} lbs ({{ detailShipment.package_type }})</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Plan Asignado:</span>
                            <p class="font-mono font-bold text-[var(--maya-primary)]">{{ detailShipment.task_title || detailShipment.assigned_task?.title || 'Sin ruta' }}</p>
                        </div>
                    </div>

                    <!-- Dirección de entrega -->
                    <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-3 text-xs">
                        <span class="font-semibold text-[var(--maya-text-muted)]">Dirección de Entrega:</span>
                        <p class="mt-1 text-sm font-medium text-[var(--maya-text-main)]">
                            📍 {{ detailShipment.destination_address }}
                        </p>
                        <p v-if="detailShipment.sender?.reference_point" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                            📌 Punto de referencia del cliente: {{ detailShipment.sender.reference_point }}
                        </p>
                    </div>

                    <!-- Evidencia de Entrega si está entregado -->
                    <div v-if="detailShipment.status === 'delivered'" class="rounded-xl border border-green-200 bg-green-50/50 p-4 dark:border-green-900/30 dark:bg-green-950/20">
                        <h4 class="flex items-center gap-2 text-xs font-bold text-green-800 dark:text-green-300">
                            <font-awesome-icon :icon="['fas', 'check']" />
                            Comprobante de Entrega
                        </h4>
                        <div class="mt-2 text-xs text-green-900 dark:text-green-200">
                            <p v-if="detailShipment.delivered_at">Entregado el: <strong>{{ formatDate(detailShipment.delivered_at) }}</strong></p>
                            <div v-if="detailShipment.delivered_photo_url || detailShipment.recipient_signature_url" class="mt-3 flex gap-4">
                                <div v-if="detailShipment.delivered_photo_url">
                                    <p class="text-[11px] font-semibold">Foto de Entrega:</p>
                                    <img :src="detailShipment.delivered_photo_url" alt="Foto entrega" class="mt-1 h-24 w-24 rounded-lg object-cover border" />
                                </div>
                                <div v-if="detailShipment.recipient_signature_url">
                                    <p class="text-[11px] font-semibold">Firma del Destinatario:</p>
                                    <img :src="detailShipment.recipient_signature_url" alt="Firma" class="mt-1 h-24 w-24 rounded-lg object-contain border bg-white p-1" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline de Tracking -->
                    <div>
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                            Historial de Seguimiento (Tracking Events)
                        </h4>

                        <div v-if="!detailShipment.tracking_events || detailShipment.tracking_events.length === 0" class="py-4 text-center text-xs text-[var(--maya-text-muted)]">
                            No hay eventos de seguimiento registrados aún.
                        </div>

                        <div v-else class="relative space-y-4 border-l-2 border-[var(--maya-border)] pl-4 ml-2">
                            <div
                                v-for="event in detailShipment.tracking_events"
                                :key="event.id"
                                class="relative text-xs"
                            >
                                <span class="absolute -left-[21px] top-1 h-3 w-3 rounded-full border-2 border-[var(--maya-bg-surface)] bg-[var(--maya-primary)]" />
                                <div>
                                    <span class="font-mono text-[10px] text-[var(--maya-text-muted)]">{{ formatDate(event.timestamp) }}</span>
                                    <p class="font-semibold text-[var(--maya-text-main)]">{{ event.description }}</p>
                                    <p v-if="event.location_name" class="text-[11px] text-[var(--maya-text-muted)]">📍 {{ event.location_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
