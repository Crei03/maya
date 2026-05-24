<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Filters from '@/Components/buttons/Filters.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import ModalForm from '@/Components/ModalForm.vue';

const activeSectionTitle = 'Bodegas';

const columns = [
    { key: 'code',            label: 'Código'   },
    { key: 'name',            label: 'Nombre'   },
    { key: 'location_address', label: 'Dirección' },
    { key: 'is_active',       label: 'Estado'   },
    { key: 'actions',         label: 'Acciones' },
];

const defaultVisibleColumns = columns.map((c) => c.key);
const visibleColumns = ref([...defaultVisibleColumns]);

const loading  = ref(false);
const saving   = ref(false);
const bodegas  = ref([]);
const pagination = ref(null);
const successMessage = ref('');

const filters = reactive({
    search:    '',
    is_active: '',
});

const modalOpen = ref(false);
const errors    = ref({});
const editingId = ref(null);

const showFilters = ref(false);
const currentPage = ref(1);
const perPage     = ref(15);

const form = reactive({
    code:             '',
    name:             '',
    location_address: '',
    phone:            '',
    location_coords:  null,
    is_active:        true,
});

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Buscar por nombre o código' },
    {
        key: 'is_active', type: 'select', placeholder: 'Todos los estados',
        options: [
            { id: '1', valor: 'Activo'   },
            { id: '0', valor: 'Inactivo' },
        ],
    },
];

const bodegasFormFields = computed(() => [
    { key: 'code',             label: 'Código',              type: 'text',   placeholder: 'Ej: BOD-001' },
    { key: 'name',             label: 'Nombre',              type: 'text',   placeholder: 'Ej: Bodega Central' },
    { key: 'location_address', label: 'Dirección',           type: 'text',   placeholder: 'Ej: Calle 123, Ciudad' },
    { key: 'phone',            label: 'Teléfono',            type: 'text',   placeholder: 'Opcional' },
    { key: 'location_coords',  label: 'Ubicación en mapa',   type: 'map',    defaultCenter: [-34.6037, -58.3816], defaultZoom: 13, colSpan: 2 },
    { key: 'is_active',        label: 'Activo',              type: 'switch' },
]);


const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const resetForm = () => {
    Object.assign(form, {
        code:             '',
        name:             '',
        location_address: '',
        phone:            '',
        location_coords:  null,
        is_active:        true,
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

const openEditModal = (bodega) => {
    successMessage.value = '';
    editingId.value      = bodega.id;
    Object.assign(form, {
        code:             bodega.code,
        name:             bodega.name,
        location_address: bodega.location_address,
        phone:            bodega.phone ?? '',
        location_coords:  bodega.location_coords ?? null,
        is_active:        bodega.is_active,
    });
    errors.value    = {};
    modalOpen.value = true;
};

const fetchBodegas = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('admin.bodegas.list'), {
            params: {
                search:    filters.search    || undefined,
                is_active: filters.is_active !== '' ? filters.is_active : undefined,
                page:      currentPage.value,
                per_page:  perPage.value,
            },
        });

        const payload    = response.data?.data;
        bodegas.value    = payload?.data  || [];
        pagination.value = payload        || null;

        if (payload?.current_page) currentPage.value = Number(payload.current_page);
        if (payload?.per_page)     perPage.value     = Number(payload.per_page);
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    currentPage.value = 1;
    await fetchBodegas();
};

const clearFilters = async () => {
    Object.assign(filters, { search: '', is_active: '' });
    currentPage.value = 1;
    await fetchBodegas();
};

const handlePerPageChange = async (value) => {
    perPage.value     = Math.max(1, Math.min(200, Number(value) || perPage.value));
    currentPage.value = 1;
    await fetchBodegas();
};

const handlePageChange = async (page) => {
    currentPage.value = Math.max(1, Number(page) || 1);
    await fetchBodegas();
};

const createBodega = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await window.axios.post(route('admin.bodegas.store'), { ...form });
        successMessage.value = 'Bodega creada correctamente.';
        closeModal();
        await fetchBodegas();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errors.value = { form: ['No fue posible guardar la bodega. Intenta nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const updateBodega = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await window.axios.patch(route('admin.bodegas.update', { warehouse: editingId.value }), { ...form });
        successMessage.value = 'Bodega actualizada correctamente.';
        closeModal();
        await fetchBodegas();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }
        errors.value = { form: ['No fue posible actualizar la bodega. Intenta nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const deleteBodega = async (id) => {
    if (!confirm('¿Estás seguro de eliminar esta bodega?')) return;
    try {
        await window.axios.delete(route('admin.bodegas.destroy', { warehouse: id }));
        successMessage.value = 'Bodega eliminada correctamente.';
        await fetchBodegas();
    } catch {
        alert('No fue posible eliminar la bodega. Intenta nuevamente.');
    }
};

onMounted(async () => {
    await fetchBodegas();
});
</script>

<template>
    <Head title="Bodegas" />

    <AdminLayout title="Bodegas">
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

                    <RefreshButton :loading="loading" @refresh="fetchBodegas" />

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                        @click="openModal"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        Agregar bodega
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
                    :rows="bodegas"
                    :visible-columns="visibleColumns"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    :min-per-page="1"
                    :max-per-page="200"
                    empty-text="No hay bodegas registradas todavía."
                    @update:per-page="handlePerPageChange"
                    @change-page="handlePageChange"
                    @edit="openEditModal"
                    @delete="deleteBodega"
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
                                @click="deleteBodega(row.id)"
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
            :title="editingId ? 'Editar bodega' : 'Nueva bodega'"
            :description="editingId
                ? 'Modifica los datos de la bodega.'
                : 'Completa los datos para registrar una nueva bodega en el sistema.'"
            :fields="bodegasFormFields"
            :errors="errors"
            :loading="saving"
            :submit-label="editingId ? 'Actualizar bodega' : 'Guardar bodega'"
            :columns="1"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="editingId ? updateBodega() : createBodega()"
        />
    </AdminLayout>
</template>
