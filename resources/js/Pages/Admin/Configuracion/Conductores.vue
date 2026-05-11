<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Filters from '@/Components/buttons/Filters.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import Modal from '@/Components/Modal.vue';
import ModalForm from '@/Components/ModalForm.vue';

const activeSectionTitle = 'Conductores';

const columns = [
    { key: 'name',              label: 'Nombre'           },
    { key: 'email',             label: 'Correo'           },
    { key: 'phone',             label: 'Teléfono'         },
    { key: 'license_number',    label: 'Licencia'         },
    { key: 'is_available',      label: 'Disponibilidad'   },
    { key: 'active_tasks_count', label: 'Tareas activas'  },
    { key: 'actions',           label: 'Acciones'         },
];

const defaultVisibleColumns = columns.map((c) => c.key);
const visibleColumns = ref([...defaultVisibleColumns]);

const loading  = ref(false);
const saving   = ref(false);
const drivers  = ref([]);
const pagination = ref(null);
const successMessage = ref('');

const filters = reactive({
    search:       '',
    is_available: '',
});

const modalOpen = ref(false);
const errors    = ref({});
const editingId = ref(null);

const showFilters = ref(false);
const currentPage = ref(1);
const perPage     = ref(15);
const confirmDeleteId = ref(null);

const driverToDelete = computed(() => {
    if (!confirmDeleteId.value) return null;
    return drivers.value.find((d) => d.id === confirmDeleteId.value) ?? null;
});

const form = reactive({
    name:              '',
    email:             '',
    phone:             '',
    password:          '',
    license_number:    '',
    license_expiry:    '',
    emergency_contact: '',
    emergency_phone:   '',
    is_available:      true,
    status:            true,
});

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Buscar por nombre o correo' },
    {
        key: 'is_available', type: 'select', placeholder: 'Todos',
        options: [
            { id: '',     valor: 'Todos'       },
            { id: '1',    valor: 'Disponible'  },
            { id: '0',    valor: 'Ocupado'     },
        ],
    },
];

const driverFormFields = computed(() => [
    { key: 'name',       label: 'Nombre',                   type: 'text',  placeholder: 'Nombre completo'                     },
    { key: 'email',      label: 'Correo',                   type: 'text',  placeholder: 'correo@ejemplo.com'                 },
    { key: 'phone',      label: 'Teléfono',                 type: 'text',  placeholder: 'Opcional'                             },
    {
        key: 'password',
        label: editingId.value ? 'Nueva contraseña (opcional)' : 'Contraseña',
        type: 'text',
        placeholder: editingId.value ? 'Dejar vacío para mantener' : 'Mínimo 8 caracteres',
        isPassword: true,
    },
    { key: 'license_number',    label: 'No. Licencia',      type: 'text',  placeholder: 'Opcional'                             },
    { key: 'license_expiry',    label: 'Venc. Licencia',    type: 'date',  placeholder: ''                                     },
    { key: 'emergency_contact', label: 'Contacto emergencia', type: 'text', placeholder: 'Nombre del contacto'                  },
    { key: 'emergency_phone',   label: 'Tel. emergencia',   type: 'text',  placeholder: 'Opcional'                             },
    { key: 'is_available',      label: 'Disponible',        type: 'switch'                                                      },
    { key: 'status',            label: 'Activo',            type: 'switch'                                                      },
]);

const toolbarSubtitle = computed(() => {
    if (!pagination.value) return 'Sin datos cargados';
    return `Mostrando ${pagination.value.from || 0}-${pagination.value.to || 0} de ${pagination.value.total || 0} conductores`;
});

const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const resetForm = () => {
    Object.assign(form, {
        name:              '',
        email:             '',
        phone:             '',
        password:          '',
        license_number:    '',
        license_expiry:    '',
        emergency_contact: '',
        emergency_phone:   '',
        is_available:      true,
        status:            true,
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

const openEditModal = (driver) => {
    successMessage.value = '';
    editingId.value      = driver.id;
    Object.assign(form, {
        name:              driver.name,
        email:             driver.email,
        phone:             driver.phone ?? '',
        password:          '',
        license_number:    driver.license_number ?? '',
        license_expiry:    driver.license_expiry ?? '',
        emergency_contact: driver.emergency_contact ?? '',
        emergency_phone:   driver.emergency_phone ?? '',
        is_available:      driver.is_available,
        status:            driver.status,
    });
    errors.value    = {};
    modalOpen.value = true;
};

const fetchDrivers = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('admin.drivers.list'), {
            params: {
                search:       filters.search       || undefined,
                is_available: filters.is_available || undefined,
                page:         currentPage.value,
                per_page:     perPage.value,
            },
        });

        const payload   = response.data?.data;
        drivers.value   = payload?.data  || [];
        pagination.value = payload       || null;

        if (payload?.current_page) currentPage.value = Number(payload.current_page);
        if (payload?.per_page)     perPage.value     = Number(payload.per_page);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    currentPage.value = 1;
    await fetchDrivers();
};

const clearFilters = async () => {
    Object.assign(filters, { search: '', is_available: '' });
    currentPage.value = 1;
    await fetchDrivers();
};

const handlePerPageChange = async (value) => {
    perPage.value     = Math.max(1, Math.min(200, Number(value) || perPage.value));
    currentPage.value = 1;
    await fetchDrivers();
};

const handlePageChange = async (page) => {
    currentPage.value = Math.max(1, Number(page) || 1);
    await fetchDrivers();
};

const createDriver = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await window.axios.post(route('admin.drivers.store'), { ...form });
        successMessage.value = 'Conductor creado correctamente.';
        closeModal();
        await fetchDrivers();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errors.value = { form: ['No fue posible guardar el conductor. Intenta nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const updateDriver = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await window.axios.patch(route('admin.drivers.update', { driver: editingId.value }), { ...form });
        successMessage.value = 'Conductor actualizado correctamente.';
        closeModal();
        await fetchDrivers();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errors.value = { form: ['No fue posible actualizar el conductor. Intenta nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const deleteDriver = (id) => {
    confirmDeleteId.value = id;
};

const confirmDelete = async () => {
    const id = confirmDeleteId.value;
    if (!id) return;

    try {
        await window.axios.delete(route('admin.drivers.destroy', { driver: id }));
        successMessage.value = 'Conductor eliminado correctamente.';
        confirmDeleteId.value = null;
        await fetchDrivers();
    } catch {
        alert('No fue posible eliminar el conductor. Intenta nuevamente.');
        confirmDeleteId.value = null;
    }
};

const cancelDelete = () => {
    confirmDeleteId.value = null;
};

onMounted(async () => {
    await fetchDrivers();
});
</script>

<template>
    <Head title="Conductores" />

    <AdminLayout title="Conductores">
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
                    <p class="mr-auto text-xs text-[var(--maya-text-muted)]">{{ toolbarSubtitle }}</p>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="showFilters = !showFilters"
                    >
                        <font-awesome-icon :icon="['fas', 'filter']" />
                        {{ showFilters ? 'Ocultar filtros' : 'Filtro' }}
                    </button>

                    <RefreshButton :loading="loading" @refresh="fetchDrivers" />

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                        @click="openModal"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        Agregar conductor
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
                    :rows="drivers"
                    :visible-columns="visibleColumns"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    :min-per-page="1"
                    :max-per-page="200"
                    empty-text="No hay conductores registrados todavía."
                    @update:per-page="handlePerPageChange"
                    @change-page="handlePageChange"
                    @edit="openEditModal"
                    @delete="deleteDriver"
                >
                    <!-- Disponibilidad -->
                    <template #cell-is_available="{ row }">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="row.is_available
                                ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success-dark)]'
                                : 'bg-[var(--maya-danger-alpha)] text-[var(--maya-danger-dark)]'"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="row.is_available ? 'bg-[var(--maya-success)]' : 'bg-[var(--maya-danger)]'"
                            />
                            {{ row.is_available ? 'Disponible' : 'Ocupado' }}
                        </span>
                    </template>

                    <!-- Tareas activas -->
                    <template #cell-active_tasks_count="{ row }">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="row.active_tasks_count > 0
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-gray-100 text-gray-500'"
                        >
                            {{ row.active_tasks_count ?? 0 }}
                        </span>
                    </template>

                    <!-- Teléfono -->
                    <template #cell-phone="{ row }">
                        <span class="text-sm text-[var(--maya-text-main)]">
                            {{ row.phone || '—' }}
                        </span>
                    </template>

                    <!-- Licencia -->
                    <template #cell-license_number="{ row }">
                        <span class="text-sm text-[var(--maya-text-main)]">
                            {{ row.license_number || '—' }}
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
                                @click="deleteDriver(row.id)"
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
            :title="editingId ? 'Editar conductor' : 'Nuevo conductor'"
            :description="editingId
                ? 'Modifica los datos del conductor.'
                : 'Completa los datos para registrar un nuevo conductor en el sistema.'"
            :fields="driverFormFields"
            :errors="errors"
            :loading="saving"
            :submit-label="editingId ? 'Actualizar conductor' : 'Guardar conductor'"
            :columns="2"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="editingId ? updateDriver() : createDriver()"
        />

        <!-- Modal confirmar eliminación -->
        <Modal :show="!!confirmDeleteId" max-width="sm" @close="cancelDelete">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--maya-danger-alpha)]">
                        <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="text-[var(--maya-danger)]" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-[var(--maya-text-main)]">Eliminar conductor</h3>
                        <p class="mt-2 text-sm text-[var(--maya-text-muted)]">
                            ¿Estás seguro de eliminar a
                            <span class="font-semibold text-[var(--maya-text-main)]">{{ driverToDelete?.name ?? 'este conductor' }}</span>?
                            Esta acción no se puede deshacer.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="cancelDelete"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-[var(--maya-danger)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-danger-dark)]"
                        @click="confirmDelete"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
