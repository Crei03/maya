<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import ModalForm from '@/Components/ModalForm.vue';
import Excel from '@/Components/buttons/Excel.vue';

// --- Estado General ---
const loading = ref(false);
const saving = ref(false);
const clients = ref([]);
const pagination = ref(null);
const currentPage = ref(1);
const perPage = ref(20);
const successMessage = ref('');
const errorMessage = ref('');

// --- Selección de Cliente (Master-Detail) ---
const selectedClient = ref(null);
const showMobileDetail = ref(false);

// --- Timeline de Entregas (Requisito: oculto por default, scroll propio, cache una sola vez) ---
const isTimelineVisible = ref(false);
const deliveriesLoading = ref(false);
const deliveriesCache = reactive({}); // cache: { [clientId]: deliveriesArray }
const currentClientDeliveries = ref([]);

// --- Filtros de la Libreta ---
const searchQuery = ref('');
const statusFilter = ref('all'); // 'all' | 'active' | 'inactive'
const searchTimeout = ref(null);

// --- Catálogos Geográficos y de Residencia ---
const paCatalog = ref([]);
const residenciaOptions = ref([]);

// --- Modal de Crear / Editar Cliente ---
const modalOpen = ref(false);
const editingId = ref(null);
const errors = ref({});

const form = reactive({
    nombre: '',
    apellido: '',
    phone: '',
    email: '',
    residencia_id: null,
    provincia_id: null,
    distrito_id: null,
    corregimiento_id: null,
    calle: '',
    numero: '',
    reference_point: '',
    codigo_postal: '',
    destination_coords: null,
    status: 'active',
    is_active: true,
});

// --- Mapa Leaflet en la Ficha del Cliente ---
const detailMapContainer = ref(null);
let detailMapInstance = null;
let detailMarkerInstance = null;

// --- Columnas para exportación Excel ---
const exportColumns = [
    { key: 'cliente', label: 'Cliente' },
    { key: 'phone', label: 'Teléfono' },
    { key: 'email', label: 'Correo' },
    { key: 'ubicacion', label: 'Ubicación' },
    { key: 'direccion', label: 'Dirección' },
    { key: 'reference_point', label: 'Referencia' },
    { key: 'deliveries_count', label: 'Entregas' },
    { key: 'status', label: 'Estado' },
];

// --- Opciones Geográficas Formulario ---
const provinceOptions = computed(() => paCatalog.value.map((province) => ({
    id: Number(province.id_provincia),
    valor: province.provincia,
})));

const selectedProvince = computed(() => paCatalog.value.find((province) => Number(province.id_provincia) === Number(form.provincia_id)) || null);

const districtOptions = computed(() => (selectedProvince.value?.distritos || []).map((district) => ({
    id: Number(district.id_distrito),
    valor: district.distrito,
})));

const selectedDistrict = computed(() => (selectedProvince.value?.distritos || []).find((district) => Number(district.id_distrito) === Number(form.distrito_id)) || null);

const corregimientoOptions = computed(() => (selectedDistrict.value?.corregimientos || []).map((corregimiento) => ({
    id: Number(corregimiento.id_corregimiento),
    valor: corregimiento.corregimiento,
})));

const clientFormFields = computed(() => [
    { key: 'nombre', label: 'Nombre *', type: 'text', placeholder: 'Ingresa el nombre' },
    { key: 'apellido', label: 'Apellido *', type: 'text', placeholder: 'Ingresa el apellido' },
    { key: 'phone', label: 'Teléfono Móvil', type: 'text', placeholder: 'Ej: +507 6123-4567' },
    { key: 'email', label: 'Correo Electrónico (Opcional)', type: 'email', placeholder: 'Ej: cliente@correo.com' },
    { key: 'residencia_id', label: 'Tipo de Residencia', type: 'select', valueType: 'number', placeholder: 'Seleccionar tipo', options: residenciaOptions.value, required: false },
    { key: 'provincia_id', label: 'Provincia', type: 'select', valueType: 'number', placeholder: 'Seleccionar provincia', options: provinceOptions.value, required: false },
    { key: 'distrito_id', label: 'Distrito', type: 'select', valueType: 'number', placeholder: 'Seleccionar distrito', options: districtOptions.value, required: false },
    { key: 'corregimiento_id', label: 'Corregimiento', type: 'select', valueType: 'number', placeholder: 'Seleccionar corregimiento', options: corregimientoOptions.value, required: false },
    { key: 'calle', label: 'Calle / Avenida', type: 'text', placeholder: 'Ej. Calle 50, Torre Global' },
    { key: 'numero', label: 'Número / Casa / Apto', type: 'text', placeholder: 'Ej. Apto 12-B' },
    { key: 'reference_point', label: 'Punto de Referencia de Entrega', type: 'text', placeholder: 'Ej. Frente a Farmacias Arrocha, portón blanco', colSpan: 2 },
    { key: 'codigo_postal', label: 'Código Postal (Opcional)', type: 'text', placeholder: 'Ej. 0801' },
    { key: 'destination_coords', label: 'Ubicación en Mapa (Opcional)', type: 'map', defaultCenter: [9.0816, -79.5000], defaultZoom: 13, colSpan: 2 },
    { key: 'is_active', label: 'Cliente Activo', type: 'switch' },
]);

// --- Funciones auxiliares de datos ---
const getInitials = (name) => {
    if (!name) return 'CL';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

const getLocationLabel = (row) => {
    if (!row) return '-';
    if (!row.provincia_id) return row.provincia || '-';
    const prov = paCatalog.value.find((p) => Number(p.id_provincia) === Number(row.provincia_id));
    if (!prov) return row.provincia || '-';
    const dist = (prov.distritos || []).find((d) => Number(d.id_distrito) === Number(row.distrito_id));
    const corr = (dist?.corregimientos || []).find((c) => Number(c.id_corregimiento) === Number(row.corregimiento_id));
    const parts = [prov.provincia, dist?.distrito, corr?.corregimiento].filter(Boolean);
    return parts.join(', ') || '-';
};

const parseCoords = (coords) => {
    if (!coords) return null;
    if (typeof coords === 'object' && coords.lat && coords.lng) {
        return { lat: Number(coords.lat), lng: Number(coords.lng) };
    }
    if (Array.isArray(coords) && coords.length >= 2) {
        return { lat: Number(coords[0]), lng: Number(coords[1]) };
    }
    if (typeof coords === 'string') {
        const parts = coords.split(',').map((p) => Number(p.trim()));
        if (parts.length >= 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
            return { lat: parts[0], lng: parts[1] };
        }
    }
    return null;
};

const selectedClientCoords = computed(() => {
    return parseCoords(selectedClient.value?.destination_coords);
});

// --- Peticiones API ---
const fetchCatalog = async (slug, params = {}) => {
    try {
        const response = await window.axios.get(route('admin.catalogos.valores', { slug }), { params });
        return response.data?.data || [];
    } catch {
        return [];
    }
};

const loadCatalogs = async () => {
    const residencia = await fetchCatalog('residencia');
    residenciaOptions.value = residencia;

    try {
        const response = await window.axios.get(route('admin.catalogos.pa.hierarchy'));
        paCatalog.value = response.data?.data || [];
    } catch {
        paCatalog.value = [];
    }
};

const fetchClients = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const params = {
            page: currentPage.value,
            per_page: perPage.value,
        };

        if (searchQuery.value.trim()) {
            params.search = searchQuery.value.trim();
        }

        if (statusFilter.value !== 'all') {
            params.status = statusFilter.value;
        }

        const response = await window.axios.get(route('admin.clients.list'), { params });
        const payload = response.data?.data;
        clients.value = payload?.data || [];
        pagination.value = payload || null;

        if (payload?.current_page) {
            currentPage.value = Number(payload.current_page);
        }

        // Si el cliente seleccionado sigue en la lista, actualizar sus datos
        if (selectedClient.value) {
            const found = clients.value.find((c) => c.id === selectedClient.value.id);
            if (found) {
                selectedClient.value = found;
            }
        } else if (clients.value.length > 0) {
            // Seleccionar por defecto el primer cliente en pantallas de escritorio
            selectClient(clients.value[0], false);
        }
    } catch {
        errorMessage.value = 'No fue posible cargar el directorio de clientes.';
    } finally {
        loading.value = false;
    }
};

// --- Selección de Cliente y Carga de Entregas ---
const selectClient = async (client, openMobile = true) => {
    selectedClient.value = client;
    if (openMobile) {
        showMobileDetail.value = true;
    }

    // Inicializar entregas desde caché si existen
    if (deliveriesCache[client.id]) {
        currentClientDeliveries.value = deliveriesCache[client.id];
    } else {
        currentClientDeliveries.value = [];
        // Si el timeline ya estaba visible, cargar entregas una sola vez
        if (isTimelineVisible.value) {
            await fetchDeliveriesForClient(client.id);
        }
    }

    // Renderizar o actualizar mini-mapa si el cliente tiene coordenadas
    nextTick(() => {
        renderDetailMap();
    });
};

const fetchDeliveriesForClient = async (clientId) => {
    if (deliveriesCache[clientId]) {
        currentClientDeliveries.value = deliveriesCache[clientId];
        return;
    }

    deliveriesLoading.value = true;
    try {
        const response = await window.axios.get(route('admin.clients.history', { id: clientId }));
        if (response.data?.success) {
            const deliveries = response.data.data?.deliveries || [];
            deliveriesCache[clientId] = deliveries;
            currentClientDeliveries.value = deliveries;
        }
    } catch (e) {
        console.warn('Error al cargar historial de entregas:', e);
        currentClientDeliveries.value = [];
    } finally {
        deliveriesLoading.value = false;
    }
};

const toggleTimeline = async () => {
    isTimelineVisible.value = !isTimelineVisible.value;
    if (isTimelineVisible.value && selectedClient.value) {
        await fetchDeliveriesForClient(selectedClient.value.id);
    }
};

// --- Inicialización y actualización de mapa de detalle ---
const renderDetailMap = () => {
    const coords = selectedClientCoords.value;
    if (!detailMapContainer.value || !coords) {
        if (detailMapInstance) {
            detailMapInstance.remove();
            detailMapInstance = null;
            detailMarkerInstance = null;
        }
        return;
    }

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    if (!detailMapInstance) {
        detailMapInstance = L.map(detailMapContainer.value, {
            zoomControl: true,
            scrollWheelZoom: false,
        }).setView([coords.lat, coords.lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
        }).addTo(detailMapInstance);

        detailMarkerInstance = L.marker([coords.lat, coords.lng]).addTo(detailMapInstance);
    } else {
        detailMapInstance.setView([coords.lat, coords.lng], 15);
        if (detailMarkerInstance) {
            detailMarkerInstance.setLatLng([coords.lat, coords.lng]);
        } else {
            detailMarkerInstance = L.marker([coords.lat, coords.lng]).addTo(detailMapInstance);
        }
        detailMapInstance.invalidateSize();
    }
};

// --- Paginación y Búsqueda ---
const onSearchInput = () => {
    if (searchTimeout.value) clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(() => {
        currentPage.value = 1;
        fetchClients();
    }, 350);
};

const clearSearch = () => {
    searchQuery.value = '';
    currentPage.value = 1;
    fetchClients();
};

const setStatusFilter = (status) => {
    statusFilter.value = status;
    currentPage.value = 1;
    fetchClients();
};

const handlePageChange = (page) => {
    const nextPage = Math.max(1, Number(page) || 1);
    currentPage.value = nextPage;
    fetchClients();
};

// --- CRUD Cliente: Modal y Guardado ---
const resetForm = () => {
    editingId.value = null;
    form.nombre = '';
    form.apellido = '';
    form.phone = '';
    form.email = '';
    form.residencia_id = null;
    form.provincia_id = null;
    form.distrito_id = null;
    form.corregimiento_id = null;
    form.calle = '';
    form.numero = '';
    form.reference_point = '';
    form.codigo_postal = '';
    form.destination_coords = null;
    form.status = 'active';
    form.is_active = true;
    errors.value = {};
};

const openCreateModal = () => {
    successMessage.value = '';
    errorMessage.value = '';
    resetForm();
    modalOpen.value = true;
};

const openEditModal = (client) => {
    successMessage.value = '';
    errorMessage.value = '';
    errors.value = {};
    editingId.value = client.id;
    form.nombre = client.nombre || client.first_name || '';
    form.apellido = client.apellido || client.last_name || '';
    form.phone = client.phone || '';
    form.email = client.email || '';
    form.residencia_id = client.residencia_id || null;
    form.provincia_id = client.provincia_id ? Number(client.provincia_id) : null;
    form.distrito_id = client.distrito_id ? Number(client.distrito_id) : null;
    form.corregimiento_id = client.corregimiento_id ? Number(client.corregimiento_id) : null;
    form.calle = client.calle || client.street_name || '';
    form.numero = client.numero || client.street_number || '';
    form.reference_point = client.reference_point || '';
    form.codigo_postal = client.codigo_postal || client.postal_code || '';
    form.destination_coords = client.destination_coords || null;
    form.status = client.status || 'active';
    form.is_active = form.status === 'active';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editingId.value = null;
};

const saveClient = async () => {
    saving.value = true;
    errors.value = {};
    errorMessage.value = '';

    const payload = {
        nombre: form.nombre,
        apellido: form.apellido,
        phone: form.phone || null,
        email: form.email || null,
        residencia_id: form.residencia_id,
        provincia_id: form.provincia_id,
        distrito_id: form.distrito_id,
        corregimiento_id: form.corregimiento_id,
        calle: form.calle || null,
        numero: form.numero || 'S/N',
        reference_point: form.reference_point || null,
        destination_coords: form.destination_coords || null,
        codigo_postal: form.codigo_postal || null,
        status: form.is_active ? 'active' : 'inactive',
    };

    try {
        let savedClient = null;
        if (editingId.value) {
            const res = await window.axios.patch(route('admin.clients.update', { id: editingId.value }), payload);
            savedClient = res.data?.data;
            successMessage.value = 'Cliente actualizado correctamente.';
            // Invalidar caché de entregas
            delete deliveriesCache[editingId.value];
        } else {
            const res = await window.axios.post(route('admin.clients.store'), payload);
            savedClient = res.data?.data;
            successMessage.value = 'Cliente registrado correctamente.';
        }

        closeModal();
        await fetchClients();

        if (savedClient) {
            selectClient(savedClient);
        }
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errorMessage.value = 'No fue posible guardar el cliente. Verifica los datos requeridos.';
    } finally {
        saving.value = false;
    }
};

const deleteClient = async (id) => {
    if (!confirm('¿Estás seguro de eliminar este cliente del directorio?')) return;
    try {
        await window.axios.delete(route('admin.clients.destroy', { id }));
        delete deliveriesCache[id];
        successMessage.value = 'Cliente eliminado correctamente.';
        selectedClient.value = null;
        showMobileDetail.value = false;
        await fetchClients();
    } catch {
        alert('No fue posible eliminar el cliente.');
    }
};

const fetchAllClientsForExport = async () => {
    const response = await window.axios.get(route('admin.clients.list'), {
        params: { per_page: 99999 },
    });

    return (response.data?.data?.data || []).map((c) => ({
        cliente: c.cliente,
        phone: c.phone || '',
        email: c.email || '',
        ubicacion: getLocationLabel(c),
        direccion: c.direccion || c.calle || '',
        reference_point: c.reference_point || '',
        deliveries_count: c.deliveries_count || 0,
        status: c.status === 'active' ? 'Activo' : 'Inactivo',
    }));
};

// --- Formatos de Fecha y Estados ---
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-PA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const getStatusLabel = (status) => {
    const map = {
        pending: 'Pendiente',
        in_warehouse: 'En bodega',
        assigned: 'Asignado',
        in_transit: 'En tránsito',
        delivered: 'Entregado',
        returned: 'Devuelto',
        failed: 'Fallido',
    };
    return map[status] || status || 'Desconocido';
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'delivered':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-300';
        case 'in_transit':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300 border-sky-300';
        case 'assigned':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300 border-purple-300';
        case 'in_warehouse':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border-amber-300';
        case 'failed':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border-rose-300';
        case 'returned':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-950/40 dark:text-orange-300 border-orange-300';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300';
    }
};

const getStatusDotColor = (status) => {
    switch (status) {
        case 'delivered':
            return 'bg-emerald-500';
        case 'in_transit':
            return 'bg-sky-500';
        case 'assigned':
            return 'bg-purple-500';
        case 'in_warehouse':
            return 'bg-amber-500';
        case 'failed':
            return 'bg-rose-500';
        case 'returned':
            return 'bg-orange-500';
        default:
            return 'bg-slate-400';
    }
};

// --- Watchers para selects en cascada del Formulario ---
watch(() => form.provincia_id, () => {
    form.distrito_id = null;
    form.corregimiento_id = null;
});

watch(() => form.distrito_id, () => {
    form.corregimiento_id = null;
});

onMounted(async () => {
    await Promise.all([loadCatalogs(), fetchClients()]);
});
</script>

<template>
    <Head title="Directorio de Clientes" />

    <AdminLayout title="Clientes">
        <div class="space-y-4">
            <!-- Barra Superior Principal -->
            <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                        <font-awesome-icon :icon="['fas', 'address-book']" class="text-xl" />
                    </span>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Libreta de Clientes</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">
                            Directorio interactivo de clientes, ficha de entrega y trazabilidad en tiempo real.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <Excel
                        :columns="exportColumns"
                        :fetch-all-data="fetchAllClientsForExport"
                        module-name="directorio_clientes"
                        :loading="loading"
                        variant="success"
                    />

                    <RefreshButton :loading="loading" @refresh="fetchClients" />

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-[var(--maya-primary)] px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] transition-colors"
                        @click="openCreateModal"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        Agregar cliente
                    </button>
                </div>
            </div>

            <!-- Notificaciones de éxito / error -->
            <div v-if="successMessage" class="rounded-xl border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] px-4 py-2.5 text-xs font-medium text-[var(--maya-success-dark)] flex items-center justify-between">
                <span>{{ successMessage }}</span>
                <button type="button" @click="successMessage = ''" class="text-xs font-bold hover:underline">
                    <font-awesome-icon :icon="['fas', 'xmark']" />
                </button>
            </div>

            <div v-if="errorMessage" class="rounded-xl border border-[var(--maya-danger)] bg-[var(--maya-danger-alpha)] px-4 py-2.5 text-xs font-medium text-[var(--maya-danger-dark)] flex items-center justify-between">
                <span>{{ errorMessage }}</span>
                <button type="button" @click="errorMessage = ''" class="text-xs font-bold hover:underline">
                    <font-awesome-icon :icon="['fas', 'xmark']" />
                </button>
            </div>

            <!-- CUADRO DIVIDIDO EN DOS PANTALLAS (ESTILO LIBRETA) -->
            <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
                
                <!-- PANTALLA IZQUIERDA: LISTA DE CLIENTES DE LA LIBRETA -->
                <div
                    class="lg:col-span-4 xl:col-span-4 border-r border-[var(--maya-border)] flex flex-col bg-[var(--maya-bg-surface)]"
                    :class="[showMobileDetail ? 'hidden lg:flex' : 'flex']"
                >
                    <!-- Cabecera de búsqueda y filtros rápidos -->
                    <div class="p-3 border-b border-[var(--maya-border)] space-y-2.5 bg-[var(--maya-bg-base)]">
                        <!-- Buscador en tiempo real -->
                        <div class="relative">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--maya-text-muted)]" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar por nombre o teléfono..."
                                class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] py-2 pl-8 pr-8 text-xs text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--maya-primary)]"
                                @input="onSearchInput"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                                @click="clearSearch"
                            >
                                <font-awesome-icon :icon="['fas', 'xmark']" />
                            </button>
                        </div>

                        <!-- Filtros rápidos de estado -->
                        <div class="flex items-center gap-1.5 text-xs">
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                                :class="statusFilter === 'all'
                                    ? 'bg-[var(--maya-primary)] text-white'
                                    : 'bg-[var(--maya-bg-surface)] text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] border border-[var(--maya-border)]'"
                                @click="setStatusFilter('all')"
                            >
                                Todos
                            </button>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                                :class="statusFilter === 'active'
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-[var(--maya-bg-surface)] text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] border border-[var(--maya-border)]'"
                                @click="setStatusFilter('active')"
                            >
                                Activos
                            </button>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                                :class="statusFilter === 'inactive'
                                    ? 'bg-red-600 text-white'
                                    : 'bg-[var(--maya-bg-surface)] text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] border border-[var(--maya-border)]'"
                                @click="setStatusFilter('inactive')"
                            >
                                Inactivos
                            </button>
                        </div>
                    </div>

                    <!-- Lista con scroll propio -->
                    <div class="flex-1 overflow-y-auto divide-y divide-[var(--maya-border)] max-h-[580px] lg:max-h-[calc(100vh-270px)]">
                        <!-- Loading State -->
                        <div v-if="loading && !clients.length" class="p-8 text-center text-xs text-[var(--maya-text-muted)]">
                            <font-awesome-icon :icon="['fas', 'spinner']" class="fa-spin text-base mb-2" />
                            <p>Cargando libreta...</p>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="!clients.length" class="p-8 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)] mb-2">
                                <font-awesome-icon :icon="['fas', 'user']" class="text-sm" />
                            </div>
                            <p class="text-xs font-semibold text-[var(--maya-text-main)]">Sin clientes encontrados</p>
                            <p class="text-[11px] text-[var(--maya-text-muted)] mt-0.5">Intenta con otro término de búsqueda.</p>
                        </div>

                        <!-- Tarjetas de Clientes en la Libreta -->
                        <div
                            v-for="c in clients"
                            :key="c.id"
                            class="p-3.5 flex items-center justify-between gap-3 cursor-pointer transition-all text-left group"
                            :class="selectedClient?.id === c.id
                                ? 'bg-[var(--maya-primary-alpha)] border-l-4 border-l-[var(--maya-primary)]'
                                : 'hover:bg-[var(--maya-hover-surface)]'"
                            @click="selectClient(c)"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Icono / Avatar con iniciales y punto de estado -->
                                <div class="relative flex-shrink-0">
                                    <div
                                        class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-xs shadow-sm border border-[var(--maya-border)]"
                                        :class="selectedClient?.id === c.id
                                            ? 'bg-[var(--maya-primary)] text-white'
                                            : 'bg-[var(--maya-hover-surface)] text-[var(--maya-text-main)] group-hover:bg-[var(--maya-primary-alpha)] group-hover:text-[var(--maya-primary)]'"
                                    >
                                        {{ getInitials(c.cliente || c.full_name) }}
                                    </div>
                                    <span
                                        class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full ring-2 ring-[var(--maya-bg-surface)]"
                                        :class="c.is_active ? 'bg-emerald-500' : 'bg-red-500'"
                                        :title="c.is_active ? 'Activo' : 'Inactivo'"
                                    />
                                </div>

                                <!-- Nombre y Teléfono del Cliente -->
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[var(--maya-text-main)] truncate group-hover:text-[var(--maya-primary)] transition-colors">
                                        {{ c.cliente || c.full_name }}
                                    </p>
                                    <p class="text-[11px] text-[var(--maya-text-muted)] flex items-center gap-1.5 mt-0.5 font-mono truncate">
                                        <font-awesome-icon :icon="['fas', 'phone']" class="text-[10px]" />
                                        {{ c.phone || 'Sin teléfono' }}
                                    </p>
                                    <p v-if="c.provincia || c.distrito" class="text-[10px] text-[var(--maya-text-muted)] truncate mt-0.5 opacity-85">
                                        {{ c.provincia }}{{ c.distrito ? ', ' + c.distrito : '' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Insignia de entregas y flecha de selección -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <font-awesome-icon
                                    :icon="['fas', 'chevron-right']"
                                    class="text-xs transition-transform"
                                    :class="selectedClient?.id === c.id ? 'text-[var(--maya-primary)] translate-x-0.5' : 'text-[var(--maya-border)] group-hover:text-[var(--maya-text-muted)]'"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Paginación compacta al pie de la libreta -->
                    <div class="p-2.5 border-t border-[var(--maya-border)] bg-[var(--maya-bg-base)] flex items-center justify-between text-xs text-[var(--maya-text-muted)]">
                        <span v-if="pagination">
                            Pág. {{ pagination.current_page }} de {{ pagination.last_page || 1 }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="!pagination?.prev_page_url"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] disabled:opacity-40 hover:bg-[var(--maya-hover-surface)] transition-colors"
                                @click="handlePageChange(currentPage - 1)"
                            >
                                <font-awesome-icon :icon="['fas', 'chevron-left']" class="text-[10px]" />
                            </button>
                            <button
                                type="button"
                                :disabled="!pagination?.next_page_url"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] disabled:opacity-40 hover:bg-[var(--maya-hover-surface)] transition-colors"
                                @click="handlePageChange(currentPage + 1)"
                            >
                                <font-awesome-icon :icon="['fas', 'chevron-right']" class="text-[10px]" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PANTALLA DERECHA: INFORMACIÓN COMPLETA DEL CLIENTE SELECCIONADO + TIMELINE -->
                <div
                    class="lg:col-span-8 xl:col-span-8 flex flex-col bg-[var(--maya-bg-surface)] overflow-y-auto"
                    :class="[!showMobileDetail ? 'hidden lg:flex' : 'flex']"
                >
                    <!-- ESTADO: NINGÚN CLIENTE SELECCIONADO -->
                    <div v-if="!selectedClient" class="m-auto p-12 text-center max-w-md">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] mb-4">
                            <font-awesome-icon :icon="['fas', 'address-book']" class="text-2xl" />
                        </div>
                        <h2 class="text-base font-bold text-[var(--maya-text-main)]">Selecciona un cliente de la libreta</h2>
                        <p class="text-xs text-[var(--maya-text-muted)] mt-1.5 leading-relaxed">
                            Pulsa sobre cualquier contacto del panel izquierdo para consultar su ficha técnica, ubicación geográfica y timeline de despachos.
                        </p>
                        <button
                            type="button"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)] transition-colors shadow-sm"
                            @click="openCreateModal"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            Registrar nuevo cliente
                        </button>
                    </div>

                    <!-- ESTADO: CLIENTE SELECCIONADO -->
                    <div v-else class="p-6 space-y-6">
                        <!-- Cabecera de la Ficha del Cliente -->
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-4 border-b border-[var(--maya-border)]">
                            <div class="flex items-start gap-4">
                                <!-- Botón volver en móvil -->
                                <button
                                    type="button"
                                    class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] mr-1"
                                    @click="showMobileDetail = false"
                                >
                                    <font-awesome-icon :icon="['fas', 'arrow-left']" />
                                </button>

                                <!-- Avatar grande -->
                                <div class="h-14 w-14 rounded-2xl bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] font-bold text-lg flex items-center justify-center border border-[var(--maya-border)] shadow-sm flex-shrink-0">
                                    {{ getInitials(selectedClient.cliente || selectedClient.full_name) }}
                                </div>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <h2 class="text-lg sm:text-xl font-bold text-[var(--maya-text-main)]">
                                            {{ selectedClient.cliente || selectedClient.full_name }}
                                        </h2>
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border"
                                            :class="selectedClient.is_active
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-300'
                                                : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300 border-red-300'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="selectedClient.is_active ? 'bg-emerald-500' : 'bg-red-500'" />
                                            {{ selectedClient.is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-[var(--maya-text-muted)] mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span v-if="selectedClient.created_at">
                                            <font-awesome-icon :icon="['fas', 'calendar']" class="mr-1 text-[10px]" />
                                            Registrado el {{ formatDate(selectedClient.created_at) }}
                                        </span>
                                        <span v-if="selectedClient.residencia">
                                            <font-awesome-icon :icon="['fas', 'house']" class="mr-1 text-[10px]" />
                                            {{ selectedClient.residencia }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Acciones del cliente seleccionado -->
                            <div class="flex items-center gap-2 self-end sm:self-start flex-shrink-0">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-1.5 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] hover:border-[var(--maya-primary)] transition-colors shadow-sm"
                                    title="Editar datos del cliente"
                                    @click="openEditModal(selectedClient)"
                                >
                                    <font-awesome-icon :icon="['fas', 'pencil']" class="text-[11px] text-[var(--maya-primary)]" />
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 hover:border-red-400 transition-colors shadow-sm"
                                    title="Eliminar cliente"
                                    @click="deleteClient(selectedClient.id)"
                                >
                                    <font-awesome-icon :icon="['fas', 'trash']" class="text-[11px]" />
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        <!-- Cuadrícula con la Información Completa del Cliente -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Tarjeta 1: Datos de Contacto -->
                            <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] p-4 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)] flex items-center gap-1.5">
                                    <font-awesome-icon :icon="['fas', 'user']" class="text-[var(--maya-primary)]" />
                                    Datos de Contacto
                                </h3>

                                <div class="space-y-2 text-xs">
                                    <!-- Teléfono -->
                                    <div class="flex items-center justify-between py-1 border-b border-[var(--maya-border)]">
                                        <span class="text-[var(--maya-text-muted)]">Teléfono móvil</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-semibold text-[var(--maya-text-main)]">
                                                {{ selectedClient.phone || 'No registrado' }}
                                            </span>
                                            <a
                                                v-if="selectedClient.phone"
                                                :href="`tel:${selectedClient.phone}`"
                                                class="text-[var(--maya-primary)] hover:underline"
                                                title="Llamar"
                                            >
                                                <font-awesome-icon :icon="['fas', 'phone']" />
                                            </a>
                                            <a
                                                v-if="selectedClient.phone"
                                                :href="`https://wa.me/${selectedClient.phone.replace(/[^0-9]/g, '')}`"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-emerald-600 hover:text-emerald-700"
                                                title="Contactar por WhatsApp"
                                            >
                                                <font-awesome-icon :icon="['fas', 'comment']" />
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Correo Electrónico -->
                                    <div class="flex items-center justify-between py-1 border-b border-[var(--maya-border)]">
                                        <span class="text-[var(--maya-text-muted)]">Correo electrónico</span>
                                        <a
                                            v-if="selectedClient.email && !selectedClient.email.includes('@maya.local')"
                                            :href="`mailto:${selectedClient.email}`"
                                            class="font-medium text-[var(--maya-primary)] hover:underline truncate max-w-[180px]"
                                        >
                                            {{ selectedClient.email }}
                                        </a>
                                        <span v-else class="text-[var(--maya-text-muted)]">No registrado</span>
                                    </div>

                                    <!-- Tipo de Residencia -->
                                    <div class="flex items-center justify-between py-1 border-b border-[var(--maya-border)]">
                                        <span class="text-[var(--maya-text-muted)]">Tipo de residencia</span>
                                        <span class="font-medium text-[var(--maya-text-main)]">
                                            {{ selectedClient.residencia || 'No especificada' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta 2: Dirección y Ubicación Geográfica -->
                            <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] p-4 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)] flex items-center gap-1.5">
                                    <font-awesome-icon :icon="['fas', 'location-dot']" class="text-[var(--maya-primary)]" />
                                    Dirección de Entrega
                                </h3>

                                <div class="space-y-2 text-xs">
                                    <!-- Provincia y Distrito -->
                                    <div class="flex items-center justify-between py-1 border-b border-[var(--maya-border)]">
                                        <span class="text-[var(--maya-text-muted)]">Provincia / Distrito</span>
                                        <span class="font-semibold text-[var(--maya-text-main)]">
                                            {{ selectedClient.provincia || '-' }} / {{ selectedClient.distrito || '-' }}
                                        </span>
                                    </div>

                                    <!-- Corregimiento -->
                                    <div class="flex items-center justify-between py-1 border-b border-[var(--maya-border)]">
                                        <span class="text-[var(--maya-text-muted)]">Corregimiento</span>
                                        <span class="font-medium text-[var(--maya-text-main)]">
                                            {{ selectedClient.corregimiento || '-' }}
                                        </span>
                                    </div>

                                    <!-- Calle y Número -->
                                    <div class="flex items-center justify-between py-1 border-b border-[var(--maya-border)]">
                                        <span class="text-[var(--maya-text-muted)]">Calle y Número</span>
                                        <span class="font-medium text-[var(--maya-text-main)] truncate max-w-[180px]" :title="selectedClient.calle">
                                            {{ selectedClient.calle || '-' }} {{ selectedClient.numero ? '#' + selectedClient.numero : '' }}
                                        </span>
                                    </div>

                                    <!-- Código Postal -->
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-[var(--maya-text-muted)]">Código Postal</span>
                                        <span class="font-mono text-[var(--maya-text-main)]">
                                            {{ selectedClient.codigo_postal || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Punto de Referencia de Entrega (Destacado) -->
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3.5 flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 rounded-lg bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] items-center justify-center flex-shrink-0 mt-0.5">
                                <font-awesome-icon :icon="['fas', 'map-pin']" class="text-sm" />
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-[var(--maya-text-main)] uppercase tracking-wider">
                                    Punto de Referencia
                                </h4>
                                <p class="text-xs text-[var(--maya-text-muted)] mt-0.5 font-medium">
                                    {{ selectedClient.reference_point || 'Sin punto de referencia específico proporcionado.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Geolocalización / Mini-mapa si tiene coordenadas -->
                        <div v-if="selectedClientCoords" class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)] flex items-center gap-1.5">
                                    <font-awesome-icon :icon="['fas', 'map']" class="text-[var(--maya-primary)]" />
                                    Coordenadas Geográficas de Entrega
                                </h3>

                                <div class="flex items-center gap-2">
                                    <a
                                        :href="`https://www.google.com/maps/search/?api=1&query=${selectedClientCoords.lat},${selectedClientCoords.lng}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2.5 py-1 text-[11px] font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition-colors"
                                    >
                                        <font-awesome-icon :icon="['fas', 'location-dot']" class="text-red-500" />
                                        Google Maps
                                    </a>
                                    <a
                                        :href="`https://waze.com/ul?ll=${selectedClientCoords.lat},${selectedClientCoords.lng}&navigate=yes`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2.5 py-1 text-[11px] font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition-colors"
                                    >
                                        <font-awesome-icon :icon="['fas', 'route']" class="text-sky-500" />
                                        Waze
                                    </a>
                                </div>
                            </div>

                            <p class="text-xs font-mono text-[var(--maya-text-muted)]">
                                Latitud: <span class="text-[var(--maya-text-main)] font-semibold">{{ selectedClientCoords.lat.toFixed(6) }}</span>,
                                Longitud: <span class="text-[var(--maya-text-main)] font-semibold">{{ selectedClientCoords.lng.toFixed(6) }}</span>
                            </p>

                            <!-- Contenedor del mapa Leaflet -->
                            <div
                                ref="detailMapContainer"
                                class="h-44 w-full rounded-xl border border-[var(--maya-border)] overflow-hidden z-10"
                            />
                        </div>

                        <!-- TIMELINE DE ENTREGAS (OCULTO POR DEFECTO, CON BOTÓN Y SCROLL PROPIO) -->
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] shadow-sm overflow-hidden">
                            <!-- Barra de control del Timeline -->
                            <div class="p-4 bg-[var(--maya-bg-base)] border-b border-[var(--maya-border)] flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                                        <font-awesome-icon :icon="['fas', 'clock-rotate-left']" class="text-sm" />
                                    </span>
                                    <div>
                                        <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--maya-text-main)]">
                                            Timeline de Entregas
                                        </h3>
                                        <p class="text-[11px] text-[var(--maya-text-muted)]">
                                            Historial secuencial de paquetes y trazabilidad de despacho.
                                        </p>
                                    </div>
                                </div>

                                <!-- Botón de visibilidad / Toggle del Timeline -->
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-1.5 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] hover:border-[var(--maya-primary)] transition-all shadow-sm"
                                    @click="toggleTimeline"
                                >
                                    <font-awesome-icon :icon="isTimelineVisible ? ['fas', 'eye-slash'] : ['fas', 'eye']" class="text-[11px] text-[var(--maya-primary)]" />
                                    <span>{{ isTimelineVisible ? 'Ocultar Timeline' : 'Ver Timeline de Entregas' }}</span>
                                    <span
                                        v-if="selectedClient.deliveries_count !== undefined"
                                        class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] font-bold bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]"
                                    >
                                        {{ selectedClient.deliveries_count }}
                                    </span>
                                </button>
                            </div>

                            <!-- Contenido del Timeline con scroll propio si está visible -->
                            <div v-if="isTimelineVisible" class="p-5">
                                <!-- Estado cargando entregas -->
                                <div v-if="deliveriesLoading" class="py-8 text-center text-xs text-[var(--maya-text-muted)]">
                                    <font-awesome-icon :icon="['fas', 'spinner']" class="fa-spin text-base mb-2 text-[var(--maya-primary)]" />
                                    <p>Cargando timeline de entregas...</p>
                                </div>

                                <!-- Estado sin entregas registradas -->
                                <div v-else-if="!currentClientDeliveries.length" class="py-8 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)] mb-2">
                                        <font-awesome-icon :icon="['fas', 'box']" class="text-sm" />
                                    </div>
                                    <p class="text-xs font-semibold text-[var(--maya-text-main)]">Sin entregas registradas</p>
                                    <p class="text-[11px] text-[var(--maya-text-muted)] mt-0.5">Aún no se han despachado paquetes para este cliente.</p>
                                </div>

                                <!-- Contenedor con SCROLL PROPIO para no alargar la página -->
                                <div v-else class="max-h-80 overflow-y-auto pr-2 space-y-6 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-[var(--maya-border)]">
                                    <div
                                        v-for="item in currentClientDeliveries"
                                        :key="item.id"
                                        class="relative flex items-start gap-4 pl-1 group"
                                    >
                                        <!-- Nodo / Punto de la línea de tiempo -->
                                        <div
                                            class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full border-2 border-[var(--maya-bg-surface)] shadow-sm flex-shrink-0"
                                            :class="getStatusDotColor(item.status)"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-white" />
                                        </div>

                                        <!-- Tarjeta del Evento / Paquete -->
                                        <div class="flex-1 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] p-3.5 hover:border-[var(--maya-primary)] transition-all shadow-xs">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-xs font-bold text-[var(--maya-primary)]">
                                                        {{ item.tracking_number }}
                                                    </span>
                                                    <span
                                                        class="text-[10px] px-2 py-0.5 rounded-full font-semibold border"
                                                        :class="getStatusBadgeClass(item.status)"
                                                    >
                                                        {{ getStatusLabel(item.status) }}
                                                    </span>
                                                </div>

                                                <span class="text-[11px] text-[var(--maya-text-muted)] font-mono">
                                                    {{ formatDate(item.created_at) }}
                                                </span>
                                            </div>

                                            <div class="mt-2 text-xs text-[var(--maya-text-muted)] space-y-1">
                                                <p v-if="item.destination_address" class="truncate" :title="item.destination_address">
                                                    <font-awesome-icon :icon="['fas', 'location-dot']" class="mr-1 text-[10px] text-[var(--maya-primary)]" />
                                                    {{ item.destination_address }}
                                                </p>
                                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] pt-1">
                                                    <span v-if="item.warehouse_name">
                                                        <font-awesome-icon :icon="['fas', 'warehouse']" class="mr-1 text-[10px]" />
                                                        Bodega: <span class="font-medium text-[var(--maya-text-main)]">{{ item.warehouse_name }}</span>
                                                    </span>
                                                    <span v-if="item.driver_name">
                                                        <font-awesome-icon :icon="['fas', 'truck']" class="mr-1 text-[10px]" />
                                                        Mensajero: <span class="font-medium text-[var(--maya-text-main)]">{{ item.driver_name }}</span>
                                                    </span>
                                                    <span v-if="item.weight_lb">
                                                        Peso: <span class="font-mono font-medium text-[var(--maya-text-main)]">{{ item.weight_lb }} lbs</span>
                                                    </span>
                                                    <span v-if="item.total_cost">
                                                        Costo: <span class="font-mono font-bold text-[var(--maya-text-main)]">${{ Number(item.total_cost).toFixed(2) }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: CREAR / EDITAR CLIENTE -->
        <ModalForm
            :model-value="form"
            :show="modalOpen"
            :title="editingId ? 'Editar Cliente' : 'Nuevo Cliente'"
            :description="editingId
                ? 'Modifica los datos del cliente en la libreta del sistema.'
                : 'Completa los datos para registrar un nuevo cliente en el directorio logístico.'"
            :fields="clientFormFields"
            :errors="errors"
            :loading="saving"
            :submit-label="editingId ? 'Actualizar Cliente' : 'Guardar Cliente'"
            :columns="2"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="saveClient"
        />
    </AdminLayout>
</template>
