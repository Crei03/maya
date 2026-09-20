<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
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
const errorMessage = ref('');
const errors = ref({});

// Left panel search filter
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
    const s = catalogSearch.value.toLowerCase().trim();
    return catalogos.value.filter((c) =>
        c.nombre.toLowerCase().includes(s) || c.slug.toLowerCase().includes(s),
    );
});

const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const selectCatalogo = async (catalogo) => {
    selectedCatalogo.value = catalogo;
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

const fetchCatalogos = async (preserveSelected = true) => {
    loading.value = true;
    try {
        const response = await window.axios.get(route('admin.configuracion.catalogos.index'));
        catalogos.value = response.data?.data || [];

        if (catalogos.value.length > 0) {
            if (preserveSelected && selectedCatalogo.value) {
                const current = catalogos.value.find((c) => c.id === selectedCatalogo.value.id);
                if (current) {
                    selectedCatalogo.value = current;
                    return;
                }
            }
            await selectCatalogo(catalogos.value[0]);
        } else {
            selectedCatalogo.value = null;
            valores.value = [];
        }
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
            if (selectedCatalogo.value && selectedCatalogo.value.slug === slug) {
                selectedCatalogo.value.valores_count = valores.value.length;
                const catInList = catalogos.value.find((c) => c.slug === slug);
                if (catInList) {
                    catInList.valores_count = valores.value.length;
                }
            }
        }
    } finally {
        loading.value = false;
    }
};

const refreshData = async () => {
    await fetchCatalogos(true);
    if (selectedCatalogo.value) {
        await fetchValores(selectedCatalogo.value.slug);
    }
};

const submitValor = async () => {
    saving.value = true;
    errors.value = {};
    errorMessage.value = '';
    try {
        if (editingId.value) {
            await window.axios.put(
                route('admin.configuracion.catalogos.valores.update', { id: editingId.value }),
                { ...form },
            );
            successMessage.value = 'Valor actualizado correctamente.';
        } else {
            await window.axios.post(
                route('admin.configuracion.catalogos.valores.store'),
                { ...form },
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
        errorMessage.value = 'Error al guardar el valor. Intente nuevamente.';
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
    await fetchCatalogos(false);
});
</script>

<template>
    <Head title="Gestión de Catálogos" />

    <AdminLayout title="Catálogos">
        <div class="space-y-4">
            <!-- Barra Superior Principal -->
            <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] transition-colors"
                        title="Volver a Configuración"
                        @click="backToSections"
                    >
                        <font-awesome-icon :icon="['fas', 'arrow-left']" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Gestión de Catálogos</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">
                            Administración centralizada de valores y clasificaciones operativas de la paquetería.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <RefreshButton :loading="loading" @refresh="refreshData" />
                    <button
                        v-if="selectedCatalogo"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-[var(--maya-primary)] px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] transition-colors"
                        @click="openCreateModal"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        Nuevo Valor
                    </button>
                </div>
            </div>

            <!-- Banner de Advertencia -->
            <div class="rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-700/50 dark:bg-amber-950/20 px-4 py-3">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 text-amber-600 dark:text-amber-400" />
                    <p class="text-xs text-amber-900 dark:text-amber-200">
                        <strong>ADVERTENCIA:</strong> Los valores aquí definidos regulan el ciclo de vida de envíos, rutas, incidentes y clasificaciones. Asegúrate de tener autorización antes de modificarlos.
                    </p>
                </div>
            </div>

            <!-- Notificaciones -->
            <div
                v-if="successMessage"
                class="rounded-xl border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] px-4 py-2.5 text-xs font-medium text-[var(--maya-success-dark)] flex items-center justify-between"
            >
                <span>{{ successMessage }}</span>
                <button type="button" @click="successMessage = ''" class="text-xs font-bold hover:underline">
                    <font-awesome-icon :icon="['fas', 'xmark']" />
                </button>
            </div>

            <div
                v-if="errorMessage"
                class="rounded-xl border border-[var(--maya-danger)] bg-[var(--maya-danger-alpha)] px-4 py-2.5 text-xs font-medium text-[var(--maya-danger-dark)] flex items-center justify-between"
            >
                <span>{{ errorMessage }}</span>
                <button type="button" @click="errorMessage = ''" class="text-xs font-bold hover:underline">
                    <font-awesome-icon :icon="['fas', 'xmark']" />
                </button>
            </div>

            <!-- Panel Maestro-Detalle Dividido -->
            <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
                
                <!-- Panel Izquierdo: Lista de Catálogos -->
                <div class="lg:col-span-4 xl:col-span-4 border-r border-[var(--maya-border)] flex flex-col bg-[var(--maya-bg-surface)]">
                    <!-- Buscador en tiempo real -->
                    <div class="p-3 border-b border-[var(--maya-border)] space-y-2.5 bg-[var(--maya-bg-base)]">
                        <div class="relative">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--maya-text-muted)]" />
                            <input
                                v-model="catalogSearch"
                                type="text"
                                placeholder="Buscar catálogo..."
                                class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] py-2 pl-8 pr-8 text-xs text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--maya-primary)]"
                            />
                            <button
                                v-if="catalogSearch"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                                @click="catalogSearch = ''"
                            >
                                <font-awesome-icon :icon="['fas', 'xmark']" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-[var(--maya-text-muted)] px-1">
                            <span>{{ filteredCatalogos.length }} catálogos disponibles</span>
                        </div>
                    </div>

                    <!-- Lista con scroll propio -->
                    <div class="flex-1 overflow-y-auto divide-y divide-[var(--maya-border)] max-h-[580px] lg:max-h-[calc(100vh-270px)]">
                        <div v-if="loading && !catalogos.length" class="p-8 text-center text-xs text-[var(--maya-text-muted)]">
                            <font-awesome-icon :icon="['fas', 'spinner']" class="fa-spin text-base mb-2" />
                            <p>Cargando catálogos...</p>
                        </div>

                        <div v-else-if="!filteredCatalogos.length" class="p-8 text-center">
                            <p class="text-xs text-[var(--maya-text-muted)]">No se encontraron catálogos coincidentes.</p>
                        </div>

                        <button
                            v-for="catalogo in filteredCatalogos"
                            :key="catalogo.id"
                            type="button"
                            class="w-full text-left p-3.5 transition-colors flex items-center justify-between group"
                            :class="selectedCatalogo?.id === catalogo.id
                                ? 'bg-[var(--maya-primary-alpha)] border-l-4 border-[var(--maya-primary)]'
                                : 'hover:bg-[var(--maya-hover-surface)]'"
                            @click="selectCatalogo(catalogo)"
                        >
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-semibold text-[var(--maya-text-main)] truncate group-hover:text-[var(--maya-primary)]">
                                    {{ catalogo.nombre }}
                                </p>
                                <p class="text-[11px] text-[var(--maya-text-muted)] font-mono truncate">
                                    {{ catalogo.slug }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                :class="selectedCatalogo?.id === catalogo.id
                                    ? 'bg-[var(--maya-primary)] text-white'
                                    : 'bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)] border border-[var(--maya-border)]'"
                            >
                                {{ catalogo.valores_count ?? 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Panel Derecho: Detalle de Valores -->
                <div class="lg:col-span-8 xl:col-span-8 flex flex-col p-4 bg-[var(--maya-bg-surface)]">
                    <!-- Cabecera del catálogo seleccionado -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-[var(--maya-border)]">
                        <div v-if="selectedCatalogo">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-[var(--maya-text-main)]">{{ selectedCatalogo.nombre }}</h2>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)] border border-[var(--maya-border)]">
                                    {{ selectedCatalogo.slug }}
                                </span>
                            </div>
                            <p class="text-xs text-[var(--maya-text-muted)] mt-1">
                                {{ selectedCatalogo.description || 'Gestión de valores y códigos del catálogo.' }}
                            </p>
                        </div>
                        <div v-else class="text-sm text-[var(--maya-text-muted)]">
                            Selecciona un catálogo del panel izquierdo para gestionar sus valores.
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="selectedCatalogo"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-[var(--maya-primary)] px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] transition-colors"
                                @click="openCreateModal"
                            >
                                <font-awesome-icon :icon="['fas', 'plus']" />
                                Nuevo Valor
                            </button>
                        </div>
                    </div>

                    <!-- DataTable de Valores -->
                    <div class="mt-4 flex-1">
                        <DataTable
                            :columns="columns"
                            :rows="valores"
                            :loading="loading"
                            empty-text="No hay valores registrados en este catálogo."
                        >
                            <template #cell-codigo="{ row }">
                                <span class="font-mono text-xs font-bold text-[var(--maya-text-main)]">{{ row.codigo }}</span>
                            </template>
                            <template #cell-valor="{ row }">
                                <span class="text-xs font-medium text-[var(--maya-text-main)]">{{ row.valor }}</span>
                            </template>
                            <template #cell-is_active="{ row }">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                    :class="row.is_active
                                        ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success-dark)]'
                                        : 'bg-[var(--maya-danger-alpha)] text-[var(--maya-danger-dark)]'"
                                >
                                    {{ row.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </template>
                            <template #cell-actions="{ row }">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] text-[var(--maya-text-muted)] hover:border-[var(--maya-primary)] hover:text-[var(--maya-primary)] transition-colors"
                                        title="Editar"
                                        @click="openEditModal(row)"
                                    >
                                        <font-awesome-icon :icon="['fas', 'pencil']" class="text-xs" />
                                    </button>
                                    <button
                                        v-if="row.tenant_id"
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] text-[var(--maya-text-muted)] hover:border-[var(--maya-danger)] hover:text-[var(--maya-danger)] transition-colors"
                                        title="Eliminar"
                                        @click="deleteValor(row.id)"
                                    >
                                        <font-awesome-icon :icon="['fas', 'trash']" class="text-xs" />
                                    </button>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar Valor -->
        <ModalForm
            :model-value="form"
            :show="modalOpen"
            :title="editingId ? 'Editar Valor' : 'Nuevo Valor'"
            :description="editingId ? 'Modifica los datos del valor.' : 'Completa los datos para registrar un nuevo valor en el catálogo.'"
            :fields="[
                { key: 'codigo', label: 'Código *', type: 'text', placeholder: 'Ej: RET' },
                { key: 'valor', label: 'Valor *', type: 'text', placeholder: 'Nombre descriptivo del valor' },
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
