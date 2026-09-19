<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
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
    { key: 'tracking_number', label: 'Tracking', class: 'whitespace-nowrap min-w-[170px]' },
    { key: 'status', label: 'Estado', class: 'whitespace-nowrap min-w-[130px]' },
    { key: 'task_title', label: 'Plan de Ruta', class: 'whitespace-nowrap min-w-[160px]' },
    { key: 'reference_info', label: 'Documento', class: 'whitespace-nowrap min-w-[170px]' },
    { key: 'recipient_name', label: 'Destinatario', class: 'min-w-[220px]' },
    { key: 'destination_address', label: 'Destino', class: 'min-w-[280px]' },
    { key: 'pieces_count', label: 'Bultos', class: 'whitespace-nowrap min-w-[95px] text-center', headerClass: 'text-center' },
    { key: 'warehouse_name', label: 'Bodega', class: 'whitespace-nowrap min-w-[150px]' },
    { key: 'package_type', label: 'Tipo', class: 'whitespace-nowrap min-w-[110px]' },
    { key: 'weight_lb', label: 'Peso (lbs)', class: 'whitespace-nowrap min-w-[125px]' },
    { key: 'created_at', label: 'Registrado', class: 'whitespace-nowrap min-w-[140px]' },
    { key: 'actions', label: 'Acciones', class: 'whitespace-nowrap min-w-[120px] text-right', headerClass: 'text-right' },
];

const defaultVisibleColumns = columns.map((c) => c.key);
const visibleColumns = ref([...defaultVisibleColumns]);
const savingColumnPreference = ref(false);

const tableMinClass = computed(() => {
    const count = visibleColumns.value.length || columns.length;
    if (count >= 10) return 'min-w-[1800px]';
    if (count >= 7) return 'min-w-[1300px]';
    if (count >= 5) return 'min-w-[950px]';
    return 'min-w-full';
});

// --- Filtros ---
const showFilters = ref(false);
const filters = reactive({
    search: '',
    status: '',
    reference_type: '',
    warehouse_id: '',
    package_type: '',
    date_from: '',
    date_to: '',
});

const props = defineProps({
    referenceTypes: { type: Array, default: () => [] },
    packageTypes: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

// --- Opciones de Catálogo Dinámicas ---
const referenceTypeOptions = computed(() => {
    if (props.referenceTypes && props.referenceTypes.length > 0) {
        return props.referenceTypes.map((rt) => ({
            id: rt.id,
            value: rt.codigo,
            label: rt.valor,
            icon: rt.metadata?.icon || 'file-lines',
        }));
    }
    return [
        { value: 'PEDIDO', label: 'Pedido / Orden', icon: 'file-invoice' },
        { value: 'FACTURA', label: 'Factura', icon: 'file-lines' },
        { value: 'TRANSFERENCIA', label: 'Transferencia', icon: 'arrow-right-arrow-left' },
        { value: 'RECIBO', label: 'Recibo', icon: 'receipt' },
        { value: 'GUIA', label: 'Guía Remisión', icon: 'truck-ramp-box' },
        { value: 'LPN', label: 'LPN / Pallet directo', icon: 'pallet' },
        { value: 'OTRO', label: 'Otro', icon: 'asterisk' },
    ];
});
const referenceTypes = referenceTypeOptions;

const packageTypeOptions = computed(() => {
    if (props.packageTypes && props.packageTypes.length > 0) {
        return props.packageTypes.map((pt) => ({
            id: pt.id,
            value: pt.codigo,
            label: pt.valor,
            icon: pt.metadata?.icon || 'box',
        }));
    }
    return [
        { value: 'CAJA', label: 'Caja', icon: 'box' },
        { value: 'PALET', label: 'Palet / Tarima', icon: 'pallet' },
        { value: 'SOBRE', label: 'Sobre', icon: 'envelope' },
        { value: 'PAQUETE', label: 'Paquete / Bulto', icon: 'boxes-stacked' },
    ];
});
const packageTypes = packageTypeOptions;

const statusOptions = computed(() => {
    if (props.statuses && props.statuses.length > 0) {
        return props.statuses.map((st) => ({
            id: st.id,
            value: st.codigo,
            label: st.valor,
            badge: st.metadata?.badge,
            icon: st.metadata?.icon,
        }));
    }
    return [
        { value: 'PENDIENTE', label: 'Pendiente' },
        { value: 'EN_BODEGA', label: 'En bodega' },
        { value: 'ASIGNADO', label: 'Asignado' },
        { value: 'EN_TRANSITO', label: 'En tránsito' },
        { value: 'ENTREGADO', label: 'Entregado' },
        { value: 'DEVUELTO', label: 'Devuelto' },
        { value: 'FALLIDO', label: 'Fallido' },
        { value: 'CANCELADO', label: 'Cancelado' },
    ];
});

// --- Vista actual y Formulario (Recepción Guiada WMS) ---
const currentView = ref('list'); // 'list' | 'form'
const editingId = ref(null);
const editingTrackingNumber = ref('');
const saving = ref(false);
const formErrors = ref({});
const lpnInputRef = ref(null);

// Modo destinatario: 'directory' (cliente del catálogo) o 'direct' (destinatario rápido/spot)
const recipientMode = ref('directory');
const saveToClientsDirectory = ref(false);

const form = reactive({
    reference_type: 'PEDIDO',
    reference_number: '',
    lpn_code: '',
    pieces_count: 1,
    sender_id: '',
    recipient_name: '',
    recipient_phone: '',
    warehouse_id: '',
    destination_address: '',
    destination_coords: '',
    package_type: 'CAJA',
    weight_lb: '',
    weight_kg: '',
    total_cost: '',
    content_description: '',
    dimensions: '',
    status: 'PENDIENTE',
});

// --- Texto completo del documento WMS para verificación ---
const documentFullText = computed(() => {
    if (!form.reference_number) return '';
    return `${(form.reference_type || 'Doc').toUpperCase()}: ${form.reference_number}`;
});

// --- Modal de visualización rápida para datos largos en verificación (>= 21 caracteres) ---
const quickViewModalOpen = ref(false);
const quickViewTitle = ref('');
const quickViewValue = ref('');
const copiedQuickView = ref(false);

const openQuickViewModal = (title, value) => {
    quickViewTitle.value = title;
    quickViewValue.value = value;
    copiedQuickView.value = false;
    quickViewModalOpen.value = true;
};

const copyQuickViewValue = async () => {
    try {
        await navigator.clipboard.writeText(quickViewValue.value);
        copiedQuickView.value = true;
        setTimeout(() => {
            copiedQuickView.value = false;
        }, 2000);
    } catch {
        // Fallback
    }
};

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
    form.recipient_name = client.full_name || `${client.first_name || ''} ${client.last_name || ''}`.trim();
    form.recipient_phone = client.phone || '';

    const addr = client.direccion || [client.calle, client.street_name, client.street_number].filter(Boolean).join(' ');
    const refPoint = client.reference_point ? ` (Ref: ${client.reference_point})` : '';
    if (addr) {
        form.destination_address = addr + refPoint;
    }
    if (client.destination_coords) {
        form.destination_coords = typeof client.destination_coords === 'object' ? JSON.stringify(client.destination_coords) : client.destination_coords;
    }
    showClientDropdown.value = false;
    clientSearchQuery.value = form.recipient_name || client.email || '';
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
const resetForm = (preserveContext = false) => {
    const defaultWarehouse = form.warehouse_id || localStorage.getItem('maya_last_warehouse_id') || warehousesList.value[0]?.id || '';
    const defaultRefType = preserveContext ? form.reference_type : 'PEDIDO';

    Object.assign(form, {
        reference_type: defaultRefType,
        reference_number: '',
        lpn_code: '',
        pieces_count: 1,
        sender_id: '',
        recipient_name: '',
        recipient_phone: '',
        warehouse_id: defaultWarehouse,
        destination_address: '',
        destination_coords: '',
        package_type: 'CAJA',
        weight_lb: '',
        weight_kg: '',
        total_cost: '',
        content_description: '',
        dimensions: '',
        status: 'PENDIENTE',
    });

    if (!preserveContext) {
        editingId.value = null;
        recipientMode.value = 'directory';
        saveToClientsDirectory.value = false;
    }
    formErrors.value = {};
    clearClient();
};

const openCreateForm = () => {
    successMessage.value = '';
    errorMessage.value = '';
    resetForm(false);
    editingTrackingNumber.value = '';
    currentView.value = 'form';
    nextTick(() => {
        lpnInputRef.value?.focus();
    });
};

const openEditForm = (shipment) => {
    successMessage.value = '';
    errorMessage.value = '';
    editingId.value = shipment.id;
    editingTrackingNumber.value = shipment.tracking_number || '';
    formErrors.value = {};

    Object.assign(form, {
        reference_type: shipment.reference_type_code || shipment.reference_type || 'PEDIDO',
        reference_number: shipment.reference_number || '',
        lpn_code: shipment.lpn_code || '',
        pieces_count: shipment.pieces_count || 1,
        sender_id: shipment.sender_id || shipment.sender?.id || '',
        recipient_name: shipment.recipient_name || shipment.sender?.full_name || '',
        recipient_phone: shipment.recipient_phone || shipment.sender?.phone || '',
        warehouse_id: shipment.warehouse_id || shipment.warehouse?.id || '',
        destination_address: shipment.destination_address || '',
        destination_coords: shipment.destination_coords ? (typeof shipment.destination_coords === 'object' ? JSON.stringify(shipment.destination_coords) : shipment.destination_coords) : '',
        package_type: shipment.package_type_code || shipment.package_type || 'CAJA',
        weight_lb: shipment.weight_lb || '',
        weight_kg: shipment.weight_kg || '',
        total_cost: shipment.total_cost || '',
        content_description: shipment.content_description || '',
        dimensions: shipment.dimensions ? (typeof shipment.dimensions === 'object' ? JSON.stringify(shipment.dimensions) : shipment.dimensions) : '',
        status: shipment.status_code || shipment.status || 'PENDIENTE',
    });

    if (shipment.sender_id) {
        recipientMode.value = 'directory';
        const found = clientsList.value.find(c => c.id === shipment.sender_id) || shipment.sender || shipment.client;
        if (found) {
            selectedClient.value = found;
            clientSearchQuery.value = found.full_name || `${found.first_name || ''} ${found.last_name || ''}`.trim() || found.email || '';
        } else {
            clearClient();
        }
    } else {
        recipientMode.value = 'direct';
        clearClient();
    }

    currentView.value = 'form';
};

const closeForm = () => {
    currentView.value = 'list';
    resetForm(false);
    fetchShipments(pagination.value?.current_page || 1);
};

const handlePerPageChange = (val) => {
    perPage.value = val;
    fetchShipments(1);
};

const onWeightLbChange = () => {
    const lb = parseFloat(form.weight_lb);
    if (!isNaN(lb) && lb > 0) {
        form.weight_kg = (lb / 2.20462).toFixed(2);
    } else {
        form.weight_kg = '';
    }
};

const onWeightKgChange = () => {
    const kg = parseFloat(form.weight_kg);
    if (!isNaN(kg) && kg > 0) {
        form.weight_lb = (kg * 2.20462).toFixed(2);
    } else {
        form.weight_lb = '';
    }
};

const saveShipment = async (andCreateAnother = false) => {
    saving.value = true;
    formErrors.value = {};
    errorMessage.value = '';

    // Recordar última bodega seleccionada
    if (form.warehouse_id) {
        localStorage.setItem('maya_last_warehouse_id', form.warehouse_id);
    }

    // Si el usuario eligió modo directo y marcó "guardar en directorio", crear el cliente en background
    if (!editingId.value && recipientMode.value === 'direct' && saveToClientsDirectory.value && form.recipient_name && !form.sender_id) {
        try {
            const clientRes = await window.axios.post(route('admin.clients.store'), {
                full_name: form.recipient_name,
                phone: form.recipient_phone || 'N/A',
                direccion: form.destination_address,
            });
            if (clientRes.data?.data?.id) {
                form.sender_id = clientRes.data.data.id;
            }
        } catch (e) {
            console.warn('No se pudo pre-crear el cliente en directorio:', e);
        }
    }

    const payload = {
        reference_type: form.reference_type || null,
        reference_number: form.reference_number ? form.reference_number.trim() : null,
        lpn_code: form.lpn_code ? form.lpn_code.trim() : null,
        pieces_count: parseInt(form.pieces_count) || 1,
        sender_id: recipientMode.value === 'directory' ? (form.sender_id || null) : (form.sender_id || null),
        recipient_name: form.recipient_name ? form.recipient_name.trim() : null,
        recipient_phone: form.recipient_phone ? form.recipient_phone.trim() : null,
        warehouse_id: form.warehouse_id,
        destination_address: form.destination_address ? form.destination_address.trim() : '',
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
        let res;
        if (editingId.value) {
            res = await window.axios.patch(route('admin.shipments.update', { id: editingId.value }), payload);
            successMessage.value = 'Paquete actualizado exitosamente.';
            closeForm();
            await fetchShipments(pagination.value?.current_page || 1);
        } else {
            res = await window.axios.post(route('admin.shipments.store'), payload);
            const createdTracking = res.data?.data?.tracking_number || '';
            const createdLpn = res.data?.data?.lpn_code ? ` [LPN: ${res.data.data.lpn_code}]` : '';

            if (andCreateAnother) {
                successMessage.value = `¡Paquete ${createdTracking}${createdLpn} registrado! Listo para escanear el siguiente.`;
                resetForm(true);
                fetchShipments(1);
                nextTick(() => {
                    lpnInputRef.value?.focus();
                });
            } else {
                successMessage.value = `Paquete ${createdTracking} creado exitosamente.`;
                closeForm();
            }
        }
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

const handleFormKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        if (!editingId.value) {
            saveShipment(true);
        } else {
            saveShipment(false);
        }
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

// Conversión de peso a kilogramos
const formatWeightKg = (lb, kg) => {
    if (kg !== null && kg !== undefined && kg !== '' && !isNaN(kg) && Number(kg) > 0) {
        return `${Number(kg).toFixed(2)} kg`;
    }
    const valLb = parseFloat(lb);
    if (!isNaN(valLb) && valLb > 0) {
        return `${(valLb / 2.20462).toFixed(2)} kg`;
    }
    return '0.00 kg';
};

onMounted(async () => {
    await fetchCatalogs();
    await fetchColumnPreferences();
    await fetchShipments(1);
});
</script>

<template>
    <Head :title="currentView === 'form' ? (editingId ? 'Editar Paquete' : 'Recepción de Envíos') : 'Gestión de Envíos'" />

    <AdminLayout :title="currentView === 'form' ? (editingId ? 'Editar Paquete' : 'Recepción WMS') : 'Envíos'">
        <!-- ==================================================================== -->
        <!-- VISTA 1: LISTADO Y TABLA DE ENVÍOS                                   -->
        <!-- ==================================================================== -->
        <div v-if="currentView === 'list'" class="space-y-6">
            <!-- Header y Acciones principales -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                                <font-awesome-icon :icon="['fas', 'boxes-stacked']" class="text-lg" />
                            </span>
                            <h1 class="text-xl font-bold text-[var(--maya-text-main)]">Gestión de Envíos y Paquetes</h1>
                        </div>
                        <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                            Registro de paquetes, control de envíos y seguimiento de entregas.
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
                            class="inline-flex items-center gap-2 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
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
                            @click="openCreateForm"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            Recepción Rápida / Nuevo
                        </button>
                    </div>
                </div>

                <!-- Mensajes -->
                <div v-if="successMessage" class="mt-4 flex items-center justify-between rounded-xl border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] p-3 text-sm text-[var(--maya-success-dark)]">
                    <span>{{ successMessage }}</span>
                    <button type="button" class="text-xs font-bold underline" @click="successMessage = ''">✕</button>
                </div>
                <div v-if="errorMessage" class="mt-4 flex items-center justify-between rounded-xl border border-[var(--maya-danger)] bg-[var(--maya-danger-alpha)] p-3 text-sm text-[var(--maya-danger)]">
                    <span>{{ errorMessage }}</span>
                    <button type="button" class="text-xs font-bold underline" @click="errorMessage = ''">✕</button>
                </div>

                <!-- Panel desplegable de Filtros -->
                <div v-if="showFilters" class="mt-5 grid grid-cols-1 gap-3 border-t border-[var(--maya-border)] pt-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Buscar</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Tracking, LPN, Pedido, Cliente..."
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
                            <option v-for="st in statusOptions" :key="st.value" :value="st.value">
                                {{ st.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Tipo Documento WMS</label>
                        <select
                            v-model="filters.reference_type"
                            class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            @change="fetchShipments(1)"
                        >
                            <option value="">Todos los documentos</option>
                            <option v-for="rt in referenceTypes" :key="rt.value" :value="rt.value">
                                {{ rt.label }}
                            </option>
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

                    <div class="flex items-end gap-2 lg:col-span-2">
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Tipo Empaque</label>
                            <select
                                v-model="filters.package_type"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                @change="fetchShipments(1)"
                            >
                                <option value="">Todos los empaques</option>
                                <option v-for="pt in packageTypes" :key="pt.value" :value="pt.value">
                                    {{ pt.label }}
                                </option>
                            </select>
                        </div>
                        <button
                            type="button"
                            class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-1.5 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                            @click="Object.assign(filters, { search: '', status: '', reference_type: '', warehouse_id: '', package_type: '', date_from: '', date_to: '' }); fetchShipments(1);"
                        >
                            Limpiar
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-[var(--maya-primary)] px-4 py-1.5 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                            @click="fetchShipments(1)"
                        >
                            Aplicar
                        </button>
                    </div>
                </div>
            </section>

            <!-- Tabla de Envíos -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-[var(--maya-border)] pb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-[var(--maya-text-main)]">
                            Listado de Envíos
                        </h3>
                        <span v-if="pagination?.total !== undefined" class="rounded-full bg-[var(--maya-primary-alpha)] px-2.5 py-0.5 font-mono text-xs font-bold text-[var(--maya-primary)]">
                            {{ pagination.total }} {{ pagination.total === 1 ? 'paquete' : 'paquetes' }}
                        </span>
                    </div>
                </div>

                <DataTable
                    :columns="columns"
                    :rows="shipments"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    :visible-columns="visibleColumns"
                    :table-class="tableMinClass"
                    empty-text="No hay envíos registrados todavía."
                    @update:per-page="handlePerPageChange"
                    @change-page="fetchShipments"
                >
                    <!-- Tracking Number y LPN -->
                    <template #cell-tracking_number="{ row }">
                        <div class="flex flex-col whitespace-nowrap">
                            <span class="font-mono text-xs font-bold text-[var(--maya-primary)] tracking-wide">
                                {{ row.tracking_number }}
                            </span>
                            <span v-if="row.lpn_code" class="mt-0.5 inline-flex items-center gap-1 font-mono text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <font-awesome-icon :icon="['fas', 'barcode']" class="text-[9px]" />
                                {{ row.lpn_code }}
                            </span>
                        </div>
                    </template>

                    <!-- Estado -->
                    <template #cell-status="{ row }">
                        <span
                            class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap"
                            :class="row.status_metadata?.badge || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'"
                        >
                            {{ row.status_label || row.status }}
                        </span>
                    </template>

                    <!-- Plan de Ruta -->
                    <template #cell-task_title="{ row }">
                        <span v-if="row.task_title || row.assigned_task?.title" class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-black dark:text-white whitespace-nowrap">
                            <font-awesome-icon :icon="['fas', 'route']" class="text-[10px] text-[var(--maya-text-muted)]" />
                            {{ row.task_title || row.assigned_task?.title }}
                        </span>
                        <span v-else class="text-xs text-[var(--maya-text-muted)] whitespace-nowrap">Sin asignar</span>
                    </template>

                    <!-- Documento -->
                    <template #cell-reference_info="{ row }">
                        <div class="text-xs whitespace-nowrap">
                            <span v-if="row.reference_number" class="font-medium text-[var(--maya-text-main)]">
                                <span class="text-[10px] uppercase text-[var(--maya-text-muted)]">{{ row.reference_type || 'DOC' }}:</span>
                                <strong class="ml-1 font-mono">{{ row.reference_number }}</strong>
                            </span>
                            <span v-else class="text-[var(--maya-text-muted)]">-</span>
                        </div>
                    </template>

                    <!-- Destinatario -->
                    <template #cell-recipient_name="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium text-[var(--maya-text-main)] leading-snug">
                                {{ row.recipient_name || row.sender?.full_name || 'Sin destinatario' }}
                            </span>
                            <span v-if="row.recipient_phone || row.sender?.phone" class="font-mono text-[11px] text-[var(--maya-text-muted)] mt-0.5">
                                {{ row.recipient_phone || row.sender?.phone }}
                            </span>
                        </div>
                    </template>

                    <!-- Destino -->
                    <template #cell-destination_address="{ row }">
                        <div class="text-xs text-[var(--maya-text-muted)] leading-relaxed" :title="row.destination_address">
                            {{ row.destination_address }}
                        </div>
                    </template>

                    <!-- Bultos -->
                    <template #cell-pieces_count="{ row }">
                        <div class="flex justify-center">
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-[var(--maya-hover-surface)] px-2.5 py-0.5 font-mono text-xs font-semibold text-[var(--maya-text-main)]">
                                <font-awesome-icon :icon="['fas', 'box']" class="text-[10px] text-[var(--maya-text-muted)]" />
                                {{ row.pieces_count || 1 }}
                            </span>
                        </div>
                    </template>

                    <!-- Bodega -->
                    <template #cell-warehouse_name="{ row }">
                        <span class="text-xs text-[var(--maya-text-main)] whitespace-nowrap">
                            {{ row.warehouse_name || row.warehouse?.name || '-' }}
                        </span>
                    </template>

                    <!-- Tipo -->
                    <template #cell-package_type="{ row }">
                        <span class="capitalize text-xs text-[var(--maya-text-muted)] whitespace-nowrap">
                            {{ row.package_type || '-' }}
                        </span>
                    </template>

                    <!-- Peso en libras con tooltip de conversión a kg -->
                    <template #cell-weight_lb="{ row }">
                        <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="font-mono text-xs font-semibold">{{ row.weight_lb }} lbs</span>
                            <span
                                class="group relative inline-flex cursor-help items-center justify-center text-[var(--maya-text-muted)] hover:text-[var(--maya-primary)] transition-colors"
                                :title="`Conversión: ${formatWeightKg(row.weight_lb, row.weight_kg)}`"
                            >
                                <font-awesome-icon :icon="['fas', 'info-circle']" class="text-[11px]" />
                                <!-- Tooltip visual flotante en hover -->
                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:flex flex-col items-center z-30">
                                    <span class="rounded-lg bg-slate-900 px-2.5 py-1 text-sm font-mono font-bold text-white shadow-xl whitespace-nowrap dark:bg-slate-800 dark:border dark:border-slate-700">
                                        ≈ {{ formatWeightKg(row.weight_lb, row.weight_kg) }}
                                    </span>
                                    <span class="w-2 h-2 rotate-45 bg-slate-900 dark:bg-slate-800 -mt-1"></span>
                                </span>
                            </span>
                        </div>
                    </template>

                    <!-- Fecha de Creación -->
                    <template #cell-created_at="{ row }">
                        <span class="text-xs text-[var(--maya-text-muted)] whitespace-nowrap">
                            {{ formatDate(row.created_at) }}
                        </span>
                    </template>

                    <!-- Acciones -->
                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] text-xs text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition shadow-2xs"
                                title="Ver Detalle y Tracking"
                                @click="openDetailModal(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'eye']" />
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] text-xs text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition shadow-2xs"
                                title="Editar Paquete"
                                @click="openEditForm(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'pencil']" />
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 text-xs text-red-600 hover:bg-red-50 dark:border-red-900/30 dark:hover:bg-red-950/20 transition shadow-2xs"
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
        <!-- VISTA 2: FORMULARIO DE RECEPCIÓN / EDICIÓN WMS A ÚLTIMA MILLA         -->
        <!-- ==================================================================== -->
        <div v-else-if="currentView === 'form'" class="space-y-6" @keydown="handleFormKeydown">
            <!-- Header de Navegación y Acciones -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition shadow-xs"
                            title="Volver al listado de envíos"
                            @click="closeForm"
                        >
                            <font-awesome-icon :icon="['fas', 'arrow-left']" />
                        </button>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg font-bold text-[var(--maya-text-main)]">
                                    {{ editingId ? 'Editar Paquete / Envío' : 'Registro de Paquetes' }}
                                </h1>
                                <span v-if="editingId && editingTrackingNumber" class="rounded-lg bg-[var(--maya-primary-alpha)] px-2.5 py-0.5 font-mono text-xs font-bold text-[var(--maya-primary)]">
                                    #{{ editingTrackingNumber }}
                                </span>
                            </div>
                            <p class="text-xs text-[var(--maya-text-muted)]">
                                {{ editingId ? 'Modifica los datos del paquete registrado en el sistema.' : 'Optimizado para escaneo continuo y despacho.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Notificaciones en el formulario -->
                <div v-if="successMessage" class="mt-4 flex items-center justify-between rounded-xl border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] p-3 text-sm text-[var(--maya-success-dark)]">
                    <div class="flex items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'circle-check']" />
                        <span>{{ successMessage }}</span>
                    </div>
                    <button type="button" class="text-xs font-bold underline" @click="successMessage = ''">✕</button>
                </div>

                <div v-if="errorMessage" class="mt-4 flex items-center justify-between rounded-xl border border-[var(--maya-danger)] bg-[var(--maya-danger-alpha)] p-3 text-sm text-[var(--maya-danger)]">
                    <div class="flex items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'circle-exclamation']" />
                        <span>{{ errorMessage }}</span>
                    </div>
                    <button type="button" class="text-xs font-bold underline" @click="errorMessage = ''">✕</button>
                </div>
            </section>

            <!-- Contenedor Principal del Formulario en 2 Columnas -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Columna Izquierda: Documento WMS & Destinatario (7 cols) -->
                <div class="space-y-6 lg:col-span-7">
                    <!-- ================================================================ -->
                    <!-- BLOQUE 1: DOCUMENTO DE ORIGEN & LPN (VÍNCULO WMS)                -->
                    <!-- ================================================================ -->
                    <div class="rounded-2xl border border-sky-200/80 bg-sky-50/40 p-5 shadow-xs dark:border-sky-900/40 dark:bg-sky-950/20">
                        <div class="mb-4 flex items-center justify-between border-b border-sky-200/60 pb-3 dark:border-sky-900/30">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-sky-600 text-white shadow-xs">
                                    <font-awesome-icon :icon="['fas', 'file-invoice']" class="text-xs" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-sky-900 dark:text-sky-200">
                                        1. Documento de Origen & LPN WMS
                                    </h3>
                                    <p class="text-[11px] text-sky-700/80 dark:text-sky-400">
                                        Vinculación con el sistema de bodega (LPN, Pallet, Pedido o Factura)
                                    </p>
                                </div>
                            </div>
                            <span class="rounded-md bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-800 dark:bg-sky-900/50 dark:text-sky-300">
                                Despacho Muelle
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Código LPN / Pallet (Pistola de código de barras) -->
                            <div class="sm:col-span-1">
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Código LPN / Pallet
                                </label>
                                <div class="relative mt-1">
                                    <input
                                        ref="lpnInputRef"
                                        v-model="form.lpn_code"
                                        type="text"
                                        placeholder="Escanear LPN..."
                                        class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] pl-8 pr-3 py-2.5 font-mono text-xs font-bold text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                                    />
                                    <font-awesome-icon :icon="['fas', 'barcode']" class="absolute left-2.5 top-3 text-xs text-[var(--maya-text-muted)]" />
                                </div>
                                <p v-if="formErrors.lpn_code" class="mt-1 text-[11px] text-red-500">{{ formErrors.lpn_code[0] }}</p>
                            </div>

                            <!-- Tipo de Documento -->
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Tipo de Documento
                                </label>
                                <select
                                    v-model="form.reference_type"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                >
                                    <option v-for="rt in referenceTypes" :key="rt.value" :value="rt.value">
                                        {{ rt.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Número de Documento / Referencia -->
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    N° Documento / Pedido
                                </label>
                                <input
                                    v-model="form.reference_number"
                                    type="text"
                                    placeholder="Ej: PED-10842 o FAC-902"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2.5 font-mono text-xs text-[var(--maya-text-main)] focus:outline-none"
                                />
                                <p v-if="formErrors.reference_number" class="mt-1 text-[11px] text-red-500">{{ formErrors.reference_number[0] }}</p>
                            </div>
                        </div>

                        <!-- Bodega de Origen -->
                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Bodega de Salida / Despacho *
                            </label>
                            <select
                                v-model="form.warehouse_id"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2.5 text-xs font-medium text-[var(--maya-text-main)] focus:outline-none"
                            >
                                <option value="">Seleccionar bodega de salida...</option>
                                <option v-for="wh in warehousesList" :key="wh.id" :value="wh.id">
                                    {{ wh.name }} ({{ wh.code || 'BOD' }})
                                </option>
                            </select>
                            <p v-if="formErrors.warehouse_id" class="mt-1 text-[11px] text-red-500">{{ formErrors.warehouse_id[0] }}</p>
                        </div>
                    </div>

                    <!-- ================================================================ -->
                    <!-- BLOQUE 2: DESTINATARIO & DESTINO (SIN BLOQUEOS)                  -->
                    <!-- ================================================================ -->
                    <div class="rounded-2xl border border-sky-200/80 bg-sky-50/40 p-5 shadow-xs dark:border-sky-900/40 dark:bg-sky-950/20">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-sky-200/60 pb-3 dark:border-sky-900/30">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-xs">
                                    <font-awesome-icon :icon="['fas', 'location-dot']" class="text-xs" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-sky-900 dark:text-sky-200">
                                        2. Destinatario & Lugar de Entrega
                                    </h3>
                                    <p class="text-[11px] text-sky-700/80 dark:text-sky-400">
                                        Selecciona un cliente frecuente o ingresa el destinatario del WMS
                                    </p>
                                </div>
                            </div>

                            <!-- Toggle Directorio vs Destinatario Directo -->
                            <div class="inline-flex rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-1 text-xs">
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-1.5 transition-all"
                                    :class="recipientMode === 'directory' ? 'bg-[var(--maya-primary)] font-semibold text-white shadow-xs' : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'"
                                    @click="recipientMode = 'directory'"
                                >
                                    <font-awesome-icon :icon="['fas', 'address-book']" class="mr-1" />
                                    Directorio
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-1.5 transition-all"
                                    :class="recipientMode === 'direct' ? 'bg-[var(--maya-primary)] font-semibold text-white shadow-xs' : 'text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]'"
                                    @click="recipientMode = 'direct'; clearClient();"
                                >
                                    <font-awesome-icon :icon="['fas', 'bolt']" class="mr-1" />
                                    Destinatario Rápido
                                </button>
                            </div>
                        </div>

                        <!-- Opción A: Directorio de Clientes con Autocompletado -->
                        <div v-if="recipientMode === 'directory'" class="space-y-3">
                            <div class="relative">
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)] mb-1">
                                    Buscar Cliente Frecuente
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="clientSearchQuery"
                                        type="text"
                                        placeholder="Buscar por nombre, teléfono o correo..."
                                        class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] pl-8 pr-8 py-2.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                        @input="searchClients"
                                        @focus="searchClients"
                                    />
                                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="absolute left-2.5 top-3 text-xs text-[var(--maya-text-muted)]" />
                                    <button
                                        v-if="clientSearchQuery"
                                        type="button"
                                        class="absolute right-2.5 top-2.5 text-xs text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                                        @click="clearClient"
                                    >
                                        <font-awesome-icon :icon="['fas', 'xmark']" />
                                    </button>
                                </div>

                                <!-- Dropdown con resultados predictivos -->
                                <div
                                    v-if="showClientDropdown && clientSearchResults.length"
                                    class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] shadow-xl"
                                >
                                    <div
                                        v-for="c in clientSearchResults"
                                        :key="c.id"
                                        class="cursor-pointer border-b border-[var(--maya-border)] p-3 text-xs hover:bg-[var(--maya-hover-surface)] transition-colors last:border-b-0"
                                        @click="selectClient(c)"
                                    >
                                        <div class="flex items-center justify-between font-semibold text-[var(--maya-text-main)]">
                                            <span>{{ c.full_name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || c.email }}</span>
                                            <span class="font-mono text-[11px] text-[var(--maya-primary)]">{{ c.phone }}</span>
                                        </div>
                                        <p v-if="c.direccion || c.street_name" class="text-[11px] text-[var(--maya-text-muted)] truncate mt-1">
                                            📍 {{ c.direccion || c.street_name }}
                                            <span v-if="c.reference_point"> (Ref: {{ c.reference_point }})</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cliente seleccionado resumen -->
                            <div v-if="selectedClient" class="flex items-center justify-between rounded-xl bg-[var(--maya-primary-alpha)] border border-[var(--maya-primary)] p-3 text-xs text-[var(--maya-primary)]">
                                <div>
                                    <span class="font-bold">Cliente Seleccionado:</span>
                                    <span class="ml-1 font-semibold">{{ selectedClient.full_name || `${selectedClient.first_name || ''} ${selectedClient.last_name || ''}`.trim() }}</span>
                                    <span v-if="selectedClient.phone" class="font-mono ml-2 text-[11px]">📞 {{ selectedClient.phone }}</span>
                                </div>
                                <button type="button" class="text-xs font-bold underline hover:opacity-80" @click="clearClient">
                                    Cambiar
                                </button>
                            </div>
                        </div>

                        <!-- Opción B: Destinatario Rápido (Spot / WMS) -->
                        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Nombre del Destinatario / Empresa *
                                </label>
                                <input
                                    v-model="form.recipient_name"
                                    type="text"
                                    placeholder="Ej: Ferretería El Tornillo o Juan Pérez"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                />
                                <p v-if="formErrors.recipient_name" class="mt-1 text-[11px] text-red-500">{{ formErrors.recipient_name[0] }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Teléfono de Contacto
                                </label>
                                <input
                                    v-model="form.recipient_phone"
                                    type="text"
                                    placeholder="Ej: 6123-4567"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2.5 font-mono text-xs text-[var(--maya-text-main)] focus:outline-none"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="flex items-center gap-2 text-xs text-[var(--maya-text-muted)] cursor-pointer">
                                    <input
                                        v-model="saveToClientsDirectory"
                                        type="checkbox"
                                        class="rounded border-[var(--maya-border)] text-[var(--maya-primary)] focus:ring-0"
                                    />
                                    Guardar también en el directorio de clientes para futuros pedidos
                                </label>
                            </div>
                        </div>

                        <!-- Dirección de Destino -->
                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Dirección de Entrega (Destino) *
                            </label>
                            <input
                                v-model="form.destination_address"
                                type="text"
                                placeholder="Calle, corregimiento, edificio, piso, local, punto de referencia..."
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            />
                            <p v-if="formErrors.destination_address" class="mt-1 text-[11px] text-red-500">{{ formErrors.destination_address[0] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Carga Física, Opciones y Resumen (5 cols) -->
                <div class="lg:col-span-5 flex flex-col h-full">
                    <!-- ================================================================ -->
                    <!-- BLOQUE 3: CARGA FÍSICA & TRANSPORTE                              -->
                    <!-- ================================================================ -->
                    <div class="flex-1 flex flex-col rounded-2xl border border-sky-200/80 bg-sky-50/40 p-5 shadow-xs dark:border-sky-900/40 dark:bg-sky-950/20">
                        <div class="mb-4 flex items-center justify-between border-b border-sky-200/60 pb-3 dark:border-sky-900/30 shrink-0">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-600 text-white shadow-xs">
                                    <font-awesome-icon :icon="['fas', 'box']" class="text-xs" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-sky-900 dark:text-sky-200">
                                        3. Carga Física & Transporte
                                    </h3>
                                    <p class="text-[11px] text-sky-700/80 dark:text-sky-400">
                                        Parámetros de embalaje, peso y cubicaje
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Empaque (Tarjetas interactivas) -->
                        <div class="shrink-0">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)] mb-1.5">
                                Tipo de Empaque *
                            </label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-2">
                                <button
                                    v-for="pt in packageTypes"
                                    :key="pt.value"
                                    type="button"
                                    class="flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-medium transition-all"
                                    :class="form.package_type === pt.value
                                        ? 'border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] font-bold shadow-xs ring-1 ring-[var(--maya-primary)]'
                                        : 'border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-muted)] hover:bg-[var(--maya-hover-surface)]'"
                                    @click="form.package_type = pt.value"
                                >
                                    <font-awesome-icon :icon="['fas', pt.icon]" />
                                    {{ pt.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Bultos y Peso -->
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 shrink-0">
                            <!-- Cantidad de Bultos / Piezas -->
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Cantidad de Bultos *
                                </label>
                                <div class="mt-1 flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-xs font-bold hover:bg-[var(--maya-hover-surface)]"
                                        @click="form.pieces_count = Math.max(1, (parseInt(form.pieces_count) || 1) - 1)"
                                    >
                                        -
                                    </button>
                                    <input
                                        v-model="form.pieces_count"
                                        type="number"
                                        min="1"
                                        class="h-9 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-center font-mono text-xs font-bold text-[var(--maya-text-main)] focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-xs font-bold hover:bg-[var(--maya-hover-surface)]"
                                        @click="form.pieces_count = (parseInt(form.pieces_count) || 1) + 1"
                                    >
                                        +
                                    </button>
                                </div>
                                <p v-if="formErrors.pieces_count" class="mt-1 text-[11px] text-red-500">{{ formErrors.pieces_count[0] }}</p>
                            </div>

                            <!-- Peso en Libras y Kg -->
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Peso (lbs / kg) *
                                </label>
                                <div class="mt-1 grid grid-cols-2 gap-1.5">
                                    <div>
                                        <input
                                            v-model="form.weight_lb"
                                            type="number"
                                            step="0.1"
                                            placeholder="lbs"
                                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2.5 py-2 font-mono text-xs text-[var(--maya-text-main)] focus:outline-none"
                                            @input="onWeightLbChange"
                                        />
                                    </div>
                                    <div>
                                        <input
                                            v-model="form.weight_kg"
                                            type="number"
                                            step="0.01"
                                            placeholder="kg"
                                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] px-2.5 py-2 font-mono text-xs text-[var(--maya-text-muted)] focus:outline-none"
                                            @input="onWeightKgChange"
                                        />
                                    </div>
                                </div>
                                <p v-if="formErrors.weight_lb" class="mt-1 text-[11px] text-red-500">{{ formErrors.weight_lb[0] }}</p>
                            </div>
                        </div>

                        <!-- Dimensiones y Costo de Envío -->
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 shrink-0">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                    Dimensiones (cm)
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
                                    Costo de Envío ($)
                                </label>
                                <input
                                    v-model="form.total_cost"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 font-mono text-xs text-[var(--maya-text-main)] focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Estado (solo en edición) -->
                        <div v-if="editingId" class="mt-4 shrink-0">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Estado del Envío
                            </label>
                            <select
                                v-model="form.status"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none"
                            >
                                <option v-for="st in statusOptions" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Descripción del Contenido (crece para completar la altura) -->
                        <div class="mt-4 flex-1 flex flex-col">
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Descripción del Contenido / Observaciones
                            </label>
                            <textarea
                                v-model="form.content_description"
                                placeholder="Notas opcionales de mercancía o fragilidad..."
                                class="mt-1 w-full flex-1 min-h-[90px] rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs text-[var(--maya-text-main)] focus:outline-none resize-none"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra Inferior de Acciones con Verificación en Muelle -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <!-- Resumen en vivo: Verificación de Muelle (Lado Izquierdo) -->
                    <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                        <!-- LPN -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                                LPN
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="font-mono text-xs font-bold"
                                    :class="form.lpn_code ? 'text-[var(--maya-text-main)]' : 'text-[var(--maya-text-muted)] italic'"
                                >
                                    {{ form.lpn_code ? (form.lpn_code.length >= 21 ? form.lpn_code.slice(0, 18) + '...' : form.lpn_code) : 'Por escanear' }}
                                </span>
                                <button
                                    v-if="form.lpn_code && form.lpn_code.length >= 21"
                                    type="button"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-primary)] hover:bg-[var(--maya-hover-surface)] shadow-2xs transition"
                                    title="Ver código LPN completo"
                                    @click="openQuickViewModal('Código LPN / Pallet', form.lpn_code)"
                                >
                                    <font-awesome-icon :icon="['fas', 'eye']" class="text-[10px]" />
                                </button>
                            </div>
                        </div>

                        <div class="hidden sm:block h-7 w-px bg-[var(--maya-border)]"></div>

                        <!-- Documento -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                                Documento
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono text-xs font-semibold text-[var(--maya-text-main)]">
                                    {{ documentFullText ? (documentFullText.length >= 21 ? documentFullText.slice(0, 18) + '...' : documentFullText) : 'Sin documento' }}
                                </span>
                                <button
                                    v-if="documentFullText && documentFullText.length >= 21"
                                    type="button"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-primary)] hover:bg-[var(--maya-hover-surface)] shadow-2xs transition"
                                    title="Ver documento completo"
                                    @click="openQuickViewModal('Documento de Origen WMS', documentFullText)"
                                >
                                    <font-awesome-icon :icon="['fas', 'eye']" class="text-[10px]" />
                                </button>
                            </div>
                        </div>

                        <div class="hidden sm:block h-7 w-px bg-[var(--maya-border)]"></div>

                        <!-- Bultos / Peso -->
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                                Bultos / Peso
                            </span>
                            <span class="text-xs font-semibold text-[var(--maya-text-main)]">
                                {{ form.pieces_count || 1 }} bulto(s) · {{ form.weight_lb || 0 }} lbs
                            </span>
                        </div>

                        <div class="hidden sm:block h-7 w-px bg-[var(--maya-border)]"></div>

                        <!-- Destinatario -->
                        <div class="flex flex-col max-w-[180px] sm:max-w-[220px] 2xl:max-w-[320px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                                Destinatario
                            </span>
                            <span class="text-xs font-semibold text-[var(--maya-text-main)] truncate" :title="form.recipient_name">
                                {{ form.recipient_name || 'Sin especificar' }}
                            </span>
                        </div>
                    </div>

                    <!-- Botones de Acción (Lado Derecho) -->
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0 border-t border-[var(--maya-border)] pt-3 xl:border-t-0 xl:pt-0">
                        <button
                            type="button"
                            class="rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition"
                            @click="closeForm"
                        >
                            Cancelar
                        </button>

                        <button
                            v-if="!editingId"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)] px-4 py-2 text-xs font-bold text-[var(--maya-primary)] hover:bg-[var(--maya-primary)] hover:text-white transition disabled:opacity-50"
                            :disabled="saving"
                            title="Atajo de teclado: Ctrl + Enter"
                            @click="saveShipment(true)"
                        >
                            <font-awesome-icon :icon="['fas', 'bolt']" />
                            Guardar y Siguiente
                            <kbd class="ml-1 rounded bg-[var(--maya-primary)]/15 px-1.5 py-0.5 text-[10px] font-mono">Ctrl+↵</kbd>
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] transition disabled:opacity-50"
                            :disabled="saving"
                            @click="saveShipment(false)"
                        >
                            {{ saving ? 'Guardando...' : (editingId ? 'Actualizar Paquete' : 'Guardar y Volver') }}
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <!-- ==================================================================== -->
        <!-- MODAL: DETALLE COMPLETO Y TIMELINE DE TRACKING                       -->
        <!-- ==================================================================== -->
        <Modal :show="detailOpen" max-width="3xl" @close="closeDetailModal">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-4">
                    <div v-if="detailShipment">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-bold text-[var(--maya-primary)]">{{ detailShipment.tracking_number }}</span>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="detailShipment.status_metadata?.badge || 'bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]'"
                            >
                                {{ detailShipment.status_label || detailShipment.status }}
                            </span>
                            <span v-if="detailShipment.lpn_code" class="rounded-full bg-emerald-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                LPN: {{ detailShipment.lpn_code }}
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
                    <!-- Ficha resumen WMS & Logística -->
                    <div class="grid grid-cols-2 gap-3 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-4 text-xs sm:grid-cols-4">
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Destinatario:</span>
                            <p class="font-bold text-[var(--maya-text-main)]">{{ detailShipment.recipient_name || detailShipment.sender?.full_name || 'N/A' }}</p>
                            <p v-if="detailShipment.recipient_phone || detailShipment.sender?.phone" class="font-mono text-[11px] text-[var(--maya-text-muted)]">
                                {{ detailShipment.recipient_phone || detailShipment.sender?.phone }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Documento WMS:</span>
                            <p class="font-mono font-bold text-[var(--maya-text-main)]">
                                {{ detailShipment.reference_number ? `${(detailShipment.reference_type || 'Doc').toUpperCase()}: ${detailShipment.reference_number}` : 'Sin documento' }}
                            </p>
                            <p v-if="detailShipment.lpn_code" class="font-mono text-[11px] text-emerald-600 dark:text-emerald-400">
                                LPN: {{ detailShipment.lpn_code }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Carga / Bultos:</span>
                            <p class="font-mono font-bold text-[var(--maya-text-main)]">
                                {{ detailShipment.pieces_count || 1 }} bulto(s) · {{ detailShipment.weight_lb }} lbs
                            </p>
                            <p class="text-[11px] capitalize text-[var(--maya-text-muted)]">Tipo: {{ detailShipment.package_type }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Bodega / Ruta:</span>
                            <p class="font-bold text-[var(--maya-text-main)]">{{ detailShipment.warehouse_name || detailShipment.warehouse?.name || 'N/A' }}</p>
                            <p class="font-mono text-[11px] font-bold text-black dark:text-white">{{ detailShipment.task_title || detailShipment.assigned_task?.title || 'Sin ruta' }}</p>
                        </div>
                    </div>

                    <!-- Dirección de entrega -->
                    <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-3 text-xs">
                        <span class="font-semibold text-[var(--maya-text-muted)]">Dirección de Entrega:</span>
                        <p class="mt-1 text-sm font-medium text-[var(--maya-text-main)]">
                            📍 {{ detailShipment.destination_address }}
                        </p>
                        <p v-if="detailShipment.content_description" class="mt-1 text-xs text-[var(--maya-text-muted)]">
                            📝 Descripción: {{ detailShipment.content_description }}
                        </p>
                    </div>

                    <!-- Evidencia de Entrega si está entregado -->
                    <div v-if="detailShipment.status === 'delivered' || detailShipment.status === 'ENTREGADO' || detailShipment.status_code === 'ENTREGADO'" class="rounded-xl border border-green-200 bg-green-50/50 p-4 dark:border-green-900/30 dark:bg-green-950/20">
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

        <!-- ==================================================================== -->
        <!-- MODAL PEQUEÑO: VISUALIZACIÓN RÁPIDA DE DATO LARGO EN VERIFICACIÓN    -->
        <!-- ==================================================================== -->
        <Modal :show="quickViewModalOpen" max-width="md" @close="quickViewModalOpen = false">
            <div class="p-5">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                            <font-awesome-icon :icon="['fas', 'eye']" class="text-sm" />
                        </span>
                        <h3 class="text-sm font-bold text-[var(--maya-text-main)]">
                            {{ quickViewTitle }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] transition"
                        @click="quickViewModalOpen = false"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" class="text-base" />
                    </button>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-[var(--maya-text-muted)] mb-1.5">
                        Valor completo registrado:
                    </p>
                    <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3">
                        <p class="break-all font-mono text-xs font-bold text-[var(--maya-text-main)] select-all leading-relaxed">
                            {{ quickViewValue }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3.5 py-1.5 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition shadow-2xs"
                        @click="copyQuickViewValue"
                    >
                        <font-awesome-icon :icon="['fas', copiedQuickView ? 'check' : 'copy']" />
                        {{ copiedQuickView ? '¡Copiado!' : 'Copiar' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[var(--maya-primary)] px-4 py-1.5 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)] transition"
                        @click="quickViewModalOpen = false"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
