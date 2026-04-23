<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Filters from '@/Components/Filters.vue';
import ColumnVisibilitySelector from '@/Components/Admin/ColumnVisibilitySelector.vue';
import RefreshButton from '@/Components/Admin/RefreshButton.vue';
import ModalForm from '@/Components/Admin/ModalForm.vue';

const columnPreferenceModule = 'admin.clients';
const activeSection = ref(null);
const showFilters = ref(false);
const currentPage = ref(1);
const perPage = ref(15);

const sections = [
    { key: 'transporte', label: 'Transporte' },
    { key: 'conductor', label: 'Conductor' },
    { key: 'roles', label: 'Roles' },
];

const activeSectionTitle = computed(() => {
    if (!activeSection.value) {
        return 'Clientes';
    }

    return sections.find((section) => section.key === activeSection.value)?.label || 'Configuracion';
});

const columns = [
    { key: 'cliente', label: 'Cliente' },
    { key: 'residencia', label: 'Residencia' },
    { key: 'provincia', label: 'Provincia' },
    { key: 'distrito', label: 'Distrito' },
    { key: 'corregimiento', label: 'Corregimiento' },
    { key: 'calle', label: 'Calle' },
    { key: 'numero', label: 'Numero' },
    { key: 'codigo_postal', label: 'Codigo Postal' },
];

const defaultVisibleColumns = columns.map((column) => column.key);

const visibleColumns = ref([...defaultVisibleColumns]);

const loading = ref(false);
const saving = ref(false);
const savingColumnPreference = ref(false);
const clients = ref([]);
const pagination = ref(null);
const successMessage = ref('');

const filters = reactive({
    cliente: '',
    residencia_id: null,
    provincia_id: null,
    distrito_id: null,
    corregimiento_id: null,
    calle: '',
    numero: '',
    codigo_postal: '',
});

const modalOpen = ref(false);
const errors = ref({});

const form = reactive({
    nombre: '',
    apellido: '',
    residencia_id: null,
    provincia_id: null,
    distrito_id: null,
    corregimiento_id: null,
    calle: '',
    numero: '',
    codigo_postal: '',
});

const paCatalog = ref([]);
const residenciaOptions = ref([]);

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

const selectedFilterProvince = computed(() => paCatalog.value.find((province) => Number(province.id_provincia) === Number(filters.provincia_id)) || null);

const filterDistrictOptions = computed(() => (selectedFilterProvince.value?.distritos || []).map((district) => ({
    id: Number(district.id_distrito),
    valor: district.distrito,
})));

const selectedFilterDistrict = computed(() => (selectedFilterProvince.value?.distritos || []).find((district) => Number(district.id_distrito) === Number(filters.distrito_id)) || null);

const filterCorregimientoOptions = computed(() => (selectedFilterDistrict.value?.corregimientos || []).map((corregimiento) => ({
    id: Number(corregimiento.id_corregimiento),
    valor: corregimiento.corregimiento,
})));

const filterFields = computed(() => ([
    { key: 'cliente', type: 'text', placeholder: 'Cliente' },
    { key: 'residencia_id', type: 'select', valueType: 'number', placeholder: 'Residencia (todas)', options: residenciaOptions.value },
    { key: 'provincia_id', type: 'select', valueType: 'number', placeholder: 'Provincia (todas)', options: provinceOptions.value },
    { key: 'distrito_id', type: 'select', valueType: 'number', placeholder: 'Distrito (todos)', options: filterDistrictOptions.value },
    { key: 'corregimiento_id', type: 'select', valueType: 'number', placeholder: 'Corregimiento (todos)', options: filterCorregimientoOptions.value },
    { key: 'calle', type: 'text', placeholder: 'Calle' },
    { key: 'numero', type: 'text', placeholder: 'Numero' },
    { key: 'codigo_postal', type: 'text', placeholder: 'Codigo Postal' },
]));

const clientFormFields = computed(() => [
    { key: 'nombre', label: 'Nombre', type: 'text', placeholder: 'Ingresa el nombre' },
    { key: 'apellido', label: 'Apellido', type: 'text', placeholder: 'Ingresa el apellido' },
    { key: 'residencia_id', label: 'Residencia', type: 'select', valueType: 'number', placeholder: 'Seleccionar residencia', options: residenciaOptions.value, required: false },
    { key: 'provincia_id', label: 'Provincia', type: 'select', valueType: 'number', placeholder: 'Seleccionar provincia', options: provinceOptions.value, required: false },
    { key: 'distrito_id', label: 'Distrito', type: 'select', valueType: 'number', placeholder: 'Seleccionar distrito', options: districtOptions.value, required: false },
    { key: 'corregimiento_id', label: 'Corregimiento', type: 'select', valueType: 'number', placeholder: 'Seleccionar corregimiento', options: corregimientoOptions.value, required: false },
    { key: 'calle', label: 'Calle', type: 'text', placeholder: 'Ej. Avenida Principal' },
    { key: 'numero', label: 'Numero', type: 'text', placeholder: 'Ej. 123', colSpan: 1 },
    { key: 'codigo_postal', label: 'Codigo Postal (Opcional)', type: 'text', placeholder: 'Ej. 0801' },
]);

const toolbarSubtitle = computed(() => {
    if (!pagination.value) {
        return 'Sin datos cargados';
    }

    return `Mostrando ${pagination.value.from || 0}-${pagination.value.to || 0} de ${pagination.value.total || 0} clientes`;
});

const selectSection = (sectionKey) => {
    activeSection.value = sectionKey;
    showFilters.value = false;
};

const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const resetForm = () => {
    form.nombre = '';
    form.apellido = '';
    form.residencia_id = null;
    form.provincia_id = null;
    form.distrito_id = null;
    form.corregimiento_id = null;
    form.calle = '';
    form.numero = '';
    form.codigo_postal = '';
    errors.value = {};
};

const openModal = () => {
    successMessage.value = '';
    resetForm();
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

const fetchCatalog = async (slug, params = {}) => {
    try {
        const response = await window.axios.get(route('admin.catalogos.valores', { slug }), { params });
        return response.data?.data || [];
    } catch (error) {
        return [];
    }
};

const loadCatalogs = async () => {
    const residencia = await fetchCatalog('residencia');
    residenciaOptions.value = residencia;

    try {
        const response = await window.axios.get(route('admin.catalogos.pa.hierarchy'));
        paCatalog.value = response.data?.data || [];
    } catch (error) {
        paCatalog.value = [];
    }
};

const normalizeVisibleColumns = (value) => {
    const allowedColumns = new Set(defaultVisibleColumns);
    const normalizedColumns = Array.isArray(value)
        ? value.filter((column) => allowedColumns.has(column))
        : [];

    return normalizedColumns.length ? normalizedColumns : [...defaultVisibleColumns];
};

const loadColumnPreference = async () => {
    try {
        const response = await window.axios.get(route('admin.column-preferences.show', { module: columnPreferenceModule }));
        visibleColumns.value = normalizeVisibleColumns(response.data?.data);
    } catch (error) {
        visibleColumns.value = [...defaultVisibleColumns];
    }
};

const saveColumnPreference = async (nextColumns) => {
    const sanitizedColumns = normalizeVisibleColumns(nextColumns);
    savingColumnPreference.value = true;

    try {
        const response = await window.axios.put(route('admin.column-preferences.update', { module: columnPreferenceModule }), {
            visible_columns: sanitizedColumns,
        });

        visibleColumns.value = normalizeVisibleColumns(response.data?.data || sanitizedColumns);
        successMessage.value = 'Configuracion de columnas guardada correctamente.';
    } catch (error) {
        successMessage.value = 'No fue posible guardar la configuracion de columnas.';
    } finally {
        savingColumnPreference.value = false;
    }
};

const fetchClients = async () => {
    loading.value = true;

    try {
        const response = await window.axios.get(route('admin.clients.list'), {
            params: {
                cliente: filters.cliente || undefined,
                residencia_id: filters.residencia_id || undefined,
                provincia_id: filters.provincia_id || undefined,
                distrito_id: filters.distrito_id || undefined,
                corregimiento_id: filters.corregimiento_id || undefined,
                calle: filters.calle || undefined,
                numero: filters.numero || undefined,
                codigo_postal: filters.codigo_postal || undefined,
                page: currentPage.value,
                per_page: perPage.value,
            },
        });

        const payload = response.data?.data;
        clients.value = payload?.data || [];
        pagination.value = payload || null;

        if (payload?.current_page) {
            currentPage.value = Number(payload.current_page);
        }

        if (payload?.per_page) {
            perPage.value = Number(payload.per_page);
        }
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    currentPage.value = 1;
    await fetchClients();
};

const clearFilters = async () => {
    filters.cliente = '';
    filters.residencia_id = null;
    filters.provincia_id = null;
    filters.distrito_id = null;
    filters.corregimiento_id = null;
    filters.calle = '';
    filters.numero = '';
    filters.codigo_postal = '';
    currentPage.value = 1;
    await fetchClients();
};

const handlePerPageChange = async (value) => {
    const nextValue = Math.max(1, Math.min(200, Number(value) || perPage.value));
    perPage.value = nextValue;
    currentPage.value = 1;
    await fetchClients();
};

const handlePageChange = async (page) => {
    const nextPage = Math.max(1, Number(page) || 1);
    currentPage.value = nextPage;
    await fetchClients();
};

const createClient = async () => {
    saving.value = true;
    errors.value = {};

    try {
        await window.axios.post(route('admin.clients.store'), {
            nombre: form.nombre,
            apellido: form.apellido,
            residencia_id: form.residencia_id,
            provincia_id: form.provincia_id,
            distrito_id: form.distrito_id,
            corregimiento_id: form.corregimiento_id,
            calle: form.calle || null,
            numero: form.numero,
            codigo_postal: form.codigo_postal || null,
        });

        successMessage.value = 'Cliente creado correctamente.';
        closeModal();
        await fetchClients();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }

        errors.value = {
            form: ['No fue posible guardar el cliente. Intenta nuevamente.'],
        };
    } finally {
        saving.value = false;
    }
};

watch(
    () => form.provincia_id,
    () => {
        form.distrito_id = null;
        form.corregimiento_id = null;
    }
);

watch(
    () => form.distrito_id,
    () => {
        form.corregimiento_id = null;
    }
);

watch(
    () => filters.provincia_id,
    () => {
        filters.distrito_id = null;
        filters.corregimiento_id = null;
    }
);

watch(
    () => filters.distrito_id,
    () => {
        filters.corregimiento_id = null;
    }
);

onMounted(async () => {
    await Promise.all([loadCatalogs(), fetchClients(), loadColumnPreference()]);
});
</script>
<template>
    <Head title="Clientes" />

    <AdminLayout title="Clientes">
        <section
        :key="activeSection"
        class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm"
    >
        <div class="mb-4 flex items-center gap-3">
            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                @click="backToSections"
            >
                <font-awesome-icon :icon="['fas', 'arrow-left']" />
            </button>
            <h2 class="text-base font-semibold text-[var(--maya-text-main)]">{{ activeSectionTitle }}</h2>
        </div>
    
        <section
            v-if="activeSection === 'transporte'"
            class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6"
        >
            <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">Configuracion de transporte</h3>
            <p class="mt-2 text-sm text-[var(--maya-text-muted)]">
                En esta seccion podras administrar reglas, capacidades y parametros logísticos de transporte.
            </p>
        </section>
    
        <section
            v-else-if="activeSection === 'conductor'"
            class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6"
        >
            <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">Configuracion de conductor</h3>
            <p class="mt-2 text-sm text-[var(--maya-text-muted)]">
                Aqui se configuraran estados, atributos y reglas de operacion para conductores.
            </p>
        </section>
    
        <section
            v-else-if="activeSection === 'roles'"
            class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6"
        >
            <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">Configuracion de roles</h3>
            <p class="mt-2 text-sm text-[var(--maya-text-muted)]">
                Define permisos y acceso para roles del ecosistema MAYA.
            </p>
        </section>
    
        <section v-else class="space-y-4">
            <div v-if="successMessage" class="mt-3 rounded-md border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] px-3 py-2 text-sm text-[var(--maya-success-dark)]">
                {{ successMessage }}
            </div>
            
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                    @click="showFilters = !showFilters"
                >
                    <font-awesome-icon :icon="['fas', 'filter']" />
                    {{ showFilters ? 'Ocultar filtros' : 'Filtro' }}
                </button>
    
                <ColumnVisibilitySelector
                    :columns="columns"
                    :model-value="visibleColumns"
                    :loading="savingColumnPreference"
                    @save="saveColumnPreference"
                />
                <RefreshButton :loading="loading" @refresh="fetchClients" />
    
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                    @click="openModal"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                    Agregar cliente
                </button>
            </div>
    
            <Filters
                v-if="showFilters"
                :fields="filterFields"
                :model-value="filters"
                @update:model-value="Object.assign(filters, $event)"
                @apply="applyFilters"
                @clear="clearFilters"
            />
    
            <DataTable
                :columns="columns"
                :rows="clients"
                :visible-columns="visibleColumns"
                :loading="loading"
                :pagination="pagination"
                :per-page="perPage"
                :min-per-page="1"
                :max-per-page="200"
                empty-text="No hay clientes creados todavia."
                @update:per-page="handlePerPageChange"
                @change-page="handlePageChange"
            />
        </section>
    </section>
    <ModalForm
            :model-value="form"
            :show="modalOpen"
            title="Nuevo cliente"
            description="Completa los datos para registrar un nuevo cliente en el sistema."
            :fields="clientFormFields"
            :errors="errors"
            :loading="saving"
            submit-label="Guardar cliente"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="createClient"
    />
    </AdminLayout>
</template>