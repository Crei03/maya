<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ModalForm from '@/Components/ModalForm.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';

const columns = [
    { key: 'codigo', label: 'Código' },
    { key: 'valor', label: 'Valor' },
    { key: 'is_active', label: 'Activo' },
    { key: 'actions', label: 'Acciones' },
];

const loading = ref(false);
const saving = ref(false);
const catalogos = ref([]);
const valores = ref([]);
const selectedCatalogo = ref(null);
const successMessage = ref('');
const errors = ref({});

// Catalog selector modal
const selectorModalOpen = ref(false);
const catalogSearch = ref('');

// Valor CRUD modal
const modalOpen = ref(false);
const editingId = ref(null);

const form = reactive({
    catalogo_id: '',
    codigo: '',
    valor: '',
    is_active: true,
});

const filteredCatalogos = computed(() => {
    if (!catalogSearch.value) return catalogos.value;
    const s = catalogSearch.value.toLowerCase();
    return catalogos.value.filter(c => c.nombre.toLowerCase().includes(s));
});

const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const openSelector = () => {
    catalogSearch.value = '';
    selectorModalOpen.value = true;
};

const closeSelector = () => {
    selectorModalOpen.value = false;
};

const selectCatalogo = async (catalogo) => {
    selectedCatalogo.value = catalogo;
    selectorModalOpen.value = false;
    await fetchValores(catalogo.slug);
};

const resetForm = () => {
    Object.assign(form, {
        catalogo_id: selectedCatalogo.value?.id || '',
        codigo: '',
        valor: '',
        is_active: true,
    });
    errors.value = {};
    editingId.value = null;
};

const openCreateModal = () => {
    resetForm();
    form.catalogo_id = selectedCatalogo.value?.id || '';
    modalOpen.value = true;
};

const openEditModal = (valor) => {
    editingId.value = valor.id;
    form.catalogo_id = selectedCatalogo.value?.id || '';
    form.codigo = valor.codigo;
    form.valor = valor.valor;
    form.is_active = Boolean(valor.is_active);
    errors.value = {};
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

const fetchCatalogos = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('admin.configuracion.catalogos.index'));
        catalogos.value = response.data?.data || [];
    } finally {
        loading.value = false;
    }
};

const fetchValores = async (slug) => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('admin.configuracion.catalogos.show', { slug }));
        const data = response.data?.data;
        if (data) {
            valores.value = data.valores || [];
        }
    } finally {
        loading.value = false;
    }
};

const submitValor = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (editingId.value) {
            await window.axios.put(
                route('admin.configuracion.catalogos.valores.update', { id: editingId.value }),
                { ...form }
            );
            successMessage.value = 'Valor actualizado correctamente.';
        } else {
            await window.axios.post(
                route('admin.configuracion.catalogos.valores.store'),
                { ...form }
            );
            successMessage.value = 'Valor creado correctamente.';
        }
        closeModal();
        if (selectedCatalogo.value) {
            await fetchValores(selectedCatalogo.value.slug);
        }
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            if (error.response.data.message) {
                errors.value = { ...errors.value, form: [error.response.data.message] };
            }
            return;
        }
        errors.value = { form: ['Error de conexión. Intente nuevamente.'] };
    } finally {
        saving.value = false;
    }
};

const deleteValor = async (id) => {
    if (!confirm('¿Estás seguro de eliminar este valor?')) return;
    try {
        await window.axios.delete(route('admin.configuracion.catalogos.valores.destroy', { id }));
        successMessage.value = 'Valor eliminado correctamente.';
        if (selectedCatalogo.value) {
            await fetchValores(selectedCatalogo.value.slug);
        }
    } catch {
        alert('No fue posible eliminar el valor. Intenta nuevamente.');
    }
};

onMounted(async () => {
    await fetchCatalogos();
});
</script>

<template>
    <Head title="Catálogos" />

    <AdminLayout title="Catálogos">
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
                <h2 class="text-base font-semibold text-[var(--maya-text-main)]">Catálogos</h2>
            </div>

            <!-- Warning Banner -->
            <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/20 px-4 py-3">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 text-yellow-600 dark:text-yellow-400" />
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>ADVERTENCIA:</strong> Cualquier modificación en este módulo de catálogos puede afectar el flujo del sistema. Asegúrate de tener autorización antes de realizar cambios.
                    </p>
                </div>
            </div>

            <section class="space-y-4">
                <!-- Success message -->
                <div
                    v-if="successMessage"
                    class="rounded-md border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] px-3 py-2 text-sm text-[var(--maya-success-dark)]"
                >
                    {{ successMessage }}
                </div>

                <!-- Selected catalog info + toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div v-if="selectedCatalogo" class="text-sm font-semibold text-[var(--maya-text-main)]">
                        Catálogo seleccionado:
                        <span class="text-[var(--maya-primary)]">{{ selectedCatalogo.nombre }}</span>
                        <span class="ml-2 text-xs text-[var(--maya-text-muted)]">({{ valores.length }} valores)</span>
                    </div>
                    <div v-else class="text-sm text-[var(--maya-text-muted)]">
                        Selecciona un catálogo para gestionar sus valores.
                    </div>
                    <div class="flex items-center gap-2">
                        <RefreshButton :loading="loading" @refresh="fetchCatalogos" />
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                            @click="openSelector"
                        >
                            <font-awesome-icon :icon="['fas', 'list']" />
                            Seleccionar Catálogo
                        </button>
                        <button
                            v-if="selectedCatalogo"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                            @click="openCreateModal"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            Nuevo Valor
                        </button>
                    </div>
                </div>

                <!-- Valores DataTable -->
                <DataTable
                    :columns="columns"
                    :rows="valores"
                    :loading="loading"
                    empty-text="Selecciona un catálogo para ver sus valores."
                >
                    <template #cell-codigo="{ row }">
                        <span class="font-mono text-sm">{{ row.codigo }}</span>
                    </template>
                    <template #cell-is_active="{ row }">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="row.is_active
                                ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success-dark)]'
                                : 'bg-[var(--maya-danger-alpha)] text-[var(--maya-danger-dark)]'"
                        >
                            {{ row.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </template>
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
                                v-if="row.tenant_id"
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-[var(--maya-border)] text-[var(--maya-text-muted)] hover:border-[var(--maya-danger)] hover:text-[var(--maya-danger)] transition-colors"
                                title="Eliminar"
                                @click="deleteValor(row.id)"
                            >
                                <font-awesome-icon :icon="['fas', 'trash']" class="text-xs" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </section>
        </section>

        <!-- Catalog Selector Modal -->
        <Modal :show="selectorModalOpen" max-width="lg" @close="closeSelector">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">Seleccionar Catálogo</h3>
                <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                    Selecciona un catálogo para gestionar sus valores.
                </p>

                <!-- Search -->
                <div class="mt-4">
                    <input
                        v-model="catalogSearch"
                        type="text"
                        class="block w-full rounded-md border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm text-[var(--maya-text-main)] placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)]"
                        placeholder="Buscar catálogo..."
                    />
                </div>

                <!-- Catalog list -->
                <div class="mt-4 max-h-80 overflow-y-auto space-y-1">
                    <button
                        v-for="catalogo in filteredCatalogos"
                        :key="catalogo.id"
                        @click="selectCatalogo(catalogo)"
                        class="w-full text-left px-4 py-3 rounded-lg border border-[var(--maya-border)] hover:bg-[var(--maya-hover-surface)] transition-colors"
                        :class="{ 'border-[var(--maya-primary)] bg-[var(--maya-primary-alpha)]': selectedCatalogo?.id === catalogo.id }"
                    >
                        <div class="font-medium text-sm text-[var(--maya-text-main)]">{{ catalogo.nombre }}</div>
                        <div class="text-xs text-[var(--maya-text-muted)]">Slug: {{ catalogo.slug }}</div>
                    </button>
                    <div v-if="filteredCatalogos.length === 0" class="text-center text-sm text-[var(--maya-text-muted)] py-8">
                        No se encontraron catálogos.
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="closeSelector"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Valor Create/Edit Modal -->
        <ModalForm
            :model-value="form"
            :show="modalOpen"
            :title="editingId ? 'Editar Valor' : 'Nuevo Valor'"
            :description="editingId ? 'Modifica los datos del valor.' : 'Completa los datos para registrar un nuevo valor.'"
            :fields="[
                { key: 'codigo', label: 'Código *', type: 'text', placeholder: 'Máx. 3 caracteres' },
                { key: 'valor', label: 'Valor *', type: 'text', placeholder: 'Nombre del valor' },
                { key: 'is_active', label: 'Activo', type: 'switch' },
            ]"
            :errors="errors"
            :loading="saving"
            :submit-label="editingId ? 'Actualizar Valor' : 'Crear Valor'"
            :columns="1"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="submitValor"
        />
    </AdminLayout>
</template>
