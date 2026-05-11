<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Filters from '@/Components/buttons/Filters.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import ModalForm from '@/Components/ModalForm.vue';

const activeSectionTitle = 'Transportes';

const columns = [
    { key: 'license_plate',  label: 'Placa'    },
    { key: 'type_label',     label: 'Tipo'     },
    { key: 'brand',          label: 'Marca'    },
    { key: 'model',          label: 'Modelo'   },
    { key: 'year',           label: 'Año'      },
    { key: 'is_active',      label: 'Estado'   },
    { key: 'actions',        label: 'Acciones' },
];

const defaultVisibleColumns = columns.map((c) => c.key);
const visibleColumns = ref([...defaultVisibleColumns]);

const loading  = ref(false);
const saving   = ref(false);
const vehicles = ref([]);
const pagination = ref(null);
const successMessage = ref('');

const filters = reactive({
    search:    '',
    type:      '',
    is_active: '',
});

const modalOpen = ref(false);
const errors    = ref({});
const editingId = ref(null);

const showFilters = ref(false);
const currentPage = ref(1);
const perPage     = ref(15);

const form = reactive({
    license_plate:   '',
    type:            'internal',
    brand:           '',
    model:           '',
    year:            new Date().getFullYear(),
    capacity_kg:     '',
    capacity_volume: '',
    color:           '',
    is_active:       true,
    notes:           '',
});

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Buscar por placa' },
    {
        key: 'type', type: 'select', placeholder: 'Todos los tipos',
        options: [
            { id: 'internal', valor: 'Interno'         },
            { id: 'external', valor: 'Externo'         },
        ],
    },
];

const vehicleFormFields = computed(() => [
    { key: 'license_plate',   label: 'Placa',                 type: 'text',   placeholder: 'Ej: ABC-1234' },
    {
        key: 'type', label: 'Tipo', type: 'select',
        options: [
            { id: 'internal', valor: 'Interno' },
            { id: 'external', valor: 'Externo' },
        ],
    },
    { key: 'brand',           label: 'Marca',                 type: 'text',   placeholder: 'Ej: Toyota'  },
    { key: 'model',           label: 'Modelo',                type: 'text',   placeholder: 'Ej: Hiace'   },
    { key: 'year',            label: 'Año',                   type: 'number', placeholder: '2024'        },
    { key: 'capacity_kg',     label: 'Capacidad (kg)',         type: 'number', placeholder: 'Opcional'    },
    { key: 'capacity_volume', label: 'Capacidad volumétrica',  type: 'number',   placeholder: 'Opcional'    },
    { key: 'color',           label: 'Color',                 type: 'text',   placeholder: 'Opcional'    },
    { key: 'notes',           label: 'Notas',                 type: 'text',   placeholder: 'Opcional'    },
    { key: 'is_active',       label: 'Activo',                type: 'switch'                              },
]);


const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const resetForm = () => {
    Object.assign(form, {
        license_plate:   '',
        type:            'internal',
        brand:           '',
        model:           '',
        year:            new Date().getFullYear(),
        capacity_kg:     '',
        capacity_volume: '',
        color:           '',
        is_active:       true,
        notes:           '',
    });
    errors.value    = {};
    editingId.value = null;
};

const openModal = () => {
    successMessage.value = '';
    resetForm();
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

const openEditModal = (vehicle) => {
    successMessage.value = '';
    editingId.value      = vehicle.id;
    Object.assign(form, {
        license_plate:   vehicle.license_plate,
        type:            vehicle.type,
        brand:           vehicle.brand,
        model:           vehicle.model,
        year:            vehicle.year,
        capacity_kg:     vehicle.capacity_kg ?? '',
        capacity_volume: vehicle.capacity_volume ?? '',
        color:           vehicle.color ?? '',
        is_active:       vehicle.is_active,
        notes:           vehicle.notes ?? '',
    });
    errors.value    = {};
    modalOpen.value = true;
};

const fetchVehicles = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('admin.vehicles.list'), {
            params: {
                search:    filters.search    || undefined,
                type:      filters.type      || undefined,
                is_active: filters.is_active !== '' ? filters.is_active : undefined,
                page:      currentPage.value,
                per_page:  perPage.value,
            },
        });

        const payload    = response.data?.data;
        vehicles.value   = payload?.data  || [];
        pagination.value = payload        || null;

        if (payload?.current_page) currentPage.value = Number(payload.current_page);
        if (payload?.per_page)     perPage.value     = Number(payload.per_page);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    currentPage.value = 1;
    await fetchVehicles();
};

const clearFilters = async () => {
    Object.assign(filters, { search: '', type: '', is_active: '' });
    currentPage.value = 1;
    await fetchVehicles();
};

const handlePerPageChange = async (value) => {
    perPage.value     = Math.max(1, Math.min(200, Number(value) || perPage.value));
    currentPage.value = 1;
    await fetchVehicles();
};

const handlePageChange = async (page) => {
    currentPage.value = Math.max(1, Number(page) || 1);
    await fetchVehicles();
};

const createVehicle = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await window.axios.post(route('admin.vehicles.store'), { ...form });
        successMessage.value = 'Vehículo creado correctamente.';
        closeModal();
        await fetchVehicles();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errors.value = { form: ['No fue posible guardar el vehículo. Intenta nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const updateVehicle = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await window.axios.patch(route('admin.vehicles.update', { vehicle: editingId.value }), { ...form });
        successMessage.value = 'Vehículo actualizado correctamente.';
        closeModal();
        await fetchVehicles();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errors.value = { form: ['No fue posible actualizar el vehículo. Intenta nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const deleteVehicle = async (id) => {
    if (!confirm('¿Estás seguro de eliminar este vehículo?')) return;
    try {
        await window.axios.delete(route('admin.vehicles.destroy', { vehicle: id }));
        successMessage.value = 'Vehículo eliminado correctamente.';
        await fetchVehicles();
    } catch {
        alert('No fue posible eliminar el vehículo. Intenta nuevamente.');
    }
};

onMounted(async () => {
    await fetchVehicles();
});
</script>

<template>
    <Head title="Transportes" />

    <AdminLayout title="Transportes">
        <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm">
            <!-- Header -->
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

            <section class="space-y-4">
                <!-- Mensaje de éxito -->
                <div
                    v-if="successMessage"
                    class="mt-3 rounded-md border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] px-3 py-2 text-sm text-[var(--maya-success-dark)]"
                >
                    {{ successMessage }}
                </div>

                <!-- Toolbar -->
                <div class="flex flex-wrap items-center justify-end gap-2">

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="showFilters = !showFilters"
                    >
                        <font-awesome-icon :icon="['fas', 'filter']" />
                        {{ showFilters ? 'Ocultar filtros' : 'Filtro' }}
                    </button>

                    <RefreshButton :loading="loading" @refresh="fetchVehicles" />

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                        @click="openModal"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        Agregar vehículo
                    </button>
                </div>

                <!-- Filtros -->
                <Filters
                    v-if="showFilters"
                    :fields="filterFields"
                    :model-value="filters"
                    @update:model-value="Object.assign(filters, $event)"
                    @apply="applyFilters"
                    @clear="clearFilters"
                />

                <!-- Tabla -->
                <DataTable
                    :columns="columns"
                    :rows="vehicles"
                    :visible-columns="visibleColumns"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    :min-per-page="1"
                    :max-per-page="200"
                    empty-text="No hay vehículos registrados todavía."
                    @update:per-page="handlePerPageChange"
                    @change-page="handlePageChange"
                    @edit="openEditModal"
                    @delete="deleteVehicle"
                >
                    <!-- Estado -->
                    <template #cell-is_active="{ row }">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="row.is_active
                                ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success-dark)]'
                                : 'bg-[var(--maya-danger-alpha)] text-[var(--maya-danger-dark)]'"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="row.is_active ? 'bg-[var(--maya-success)]' : 'bg-[var(--maya-danger)]'"
                            />
                            {{ row.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </template>

                    <!-- Tipo -->
                    <template #cell-type_label="{ row }">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="row.type === 'internal'
                                ? 'bg-blue-100 text-blue-700'
                                : 'bg-purple-100 text-purple-700'"
                        >
                            {{ row.type_label }}
                        </span>
                    </template>
                    <!-- Acciones -->
                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-[var(--maya-border)] text-[var(--maya-text-muted)] hover:border-[var(--maya-primary)] hover:text-[var(--maya-primary)] transition-colors"
                                title="Editar"
                                @click="openEditModal(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'pencil']" class="text-xs" />
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-[var(--maya-border)] text-[var(--maya-text-muted)] hover:border-[var(--maya-danger)] hover:text-[var(--maya-danger)] transition-colors"
                                title="Eliminar"
                                @click="deleteVehicle(row.id)"
                            >
                                <font-awesome-icon :icon="['fas', 'trash']" class="text-xs" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </section>
        </section>

        <!-- Modal crear / editar -->
        <ModalForm
            :model-value="form"
            :show="modalOpen"
            :title="editingId ? 'Editar vehículo' : 'Nuevo vehículo'"
            :description="editingId
                ? 'Modifica los datos del vehículo.'
                : 'Completa los datos para registrar un nuevo vehículo en el sistema.'"
            :fields="vehicleFormFields"
            :errors="errors"
            :loading="saving"
            :submit-label="editingId ? 'Actualizar vehículo' : 'Guardar vehículo'"
            :columns="1"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="editingId ? updateVehicle() : createVehicle()"
        />
    </AdminLayout>
</template>
