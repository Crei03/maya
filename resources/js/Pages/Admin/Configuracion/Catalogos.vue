<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
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
const catalogos = ref([]);
const valores = ref([]);
const selectedCatalogo = ref(null);
const successMessage = ref('');
const errorMessage = ref('');

// Left panel search filter
const catalogSearch = ref('');

// --- Modal & Form: CATÁLOGO ---
const catalogoModalOpen = ref(false);
const editingCatalogoId = ref(null);
const catalogoSaving = ref(false);
const catalogoErrors = ref({});
const catalogoForm = reactive({
    nombre: '',
    slug: '',
    description: '',
});

const catalogoFields = computed(() => [
    { key: 'nombre', label: 'Nombre del Catálogo *', type: 'text', placeholder: 'Ej: Tipo de Prioridad' },
    { key: 'slug', label: 'Slug / Identificador *', type: 'text', placeholder: 'Ej: tipo-prioridad' },
    { key: 'description', label: 'Descripción (Opcional)', type: 'text', placeholder: 'Describe el propósito de este catálogo...' },
]);

// Auto-generar slug en nuevo catálogo
watch(() => catalogoForm.nombre, (val) => {
    if (!editingCatalogoId.value && val) {
        catalogoForm.slug = val
            .toLowerCase()
            .trim()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }
});

// --- Modal & Form: VALOR DEL CATÁLOGO ---
const valorModalOpen = ref(false);
const editingValorId = ref(null);
const valorSaving = ref(false);
const valorErrors = ref({});
const valorForm = reactive({
    catalogo_id: '',
    codigo: '',
    valor: '',
    is_active: true,
});

const valorFields = computed(() => [
    { key: 'codigo', label: 'Código *', type: 'text', placeholder: 'Ej: URG, RET, EST-01' },
    { key: 'valor', label: 'Valor / Nombre *', type: 'text', placeholder: 'Nombre descriptivo del valor' },
    { key: 'is_active', label: 'Activo', type: 'switch' },
]);

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

// --- CRUD: CATÁLOGO ---
const openCreateCatalogoModal = () => {
    editingCatalogoId.value = null;
    catalogoForm.nombre = '';
    catalogoForm.slug = '';
    catalogoForm.description = '';
    catalogoErrors.value = {};
    catalogoModalOpen.value = true;
};

const openEditCatalogoModal = () => {
    if (!selectedCatalogo.value) return;
    editingCatalogoId.value = selectedCatalogo.value.id;
    catalogoForm.nombre = selectedCatalogo.value.nombre;
    catalogoForm.slug = selectedCatalogo.value.slug;
    catalogoForm.description = selectedCatalogo.value.description || '';
    catalogoErrors.value = {};
    catalogoModalOpen.value = true;
};

const closeCatalogoModal = () => {
    catalogoModalOpen.value = false;
};

const submitCatalogo = async () => {
    catalogoSaving.value = true;
    catalogoErrors.value = {};
    errorMessage.value = '';
    try {
        if (editingCatalogoId.value) {
            const res = await window.axios.put(
                route('admin.configuracion.catalogos.update', { id: editingCatalogoId.value }),
                {
                    nombre: catalogoForm.nombre,
                    description: catalogoForm.description,
                },
            );
            successMessage.value = 'Catálogo actualizado correctamente.';
            catalogoModalOpen.value = false;
            const updated = res.data?.data;
            await fetchCatalogos(true);
            if (updated && selectedCatalogo.value?.id === updated.id) {
                selectedCatalogo.value.nombre = updated.nombre;
                selectedCatalogo.value.description = updated.description;
            }
        } else {
            const res = await window.axios.post(
                route('admin.configuracion.catalogos.store'),
                {
                    nombre: catalogoForm.nombre,
                    slug: catalogoForm.slug,
                    description: catalogoForm.description,
                },
            );
            successMessage.value = 'Catálogo creado correctamente.';
            catalogoModalOpen.value = false;
            const created = res.data?.data;
            await fetchCatalogos(false);
            if (created) {
                const found = catalogos.value.find((c) => c.id === created.id);
                if (found) {
                    await selectCatalogo(found);
                }
            }
        }
    } catch (error) {
        if (error?.response?.status === 422) {
            catalogoErrors.value = error.response.data.errors || {};
            return;
        }
        errorMessage.value = error?.response?.data?.message || 'Error al guardar el catálogo.';
    } finally {
        catalogoSaving.value = false;
    }
};

const deleteCatalogo = async (catalogo) => {
    if (!catalogo) return;
    if (!confirm(`¿Estás seguro de eliminar el catálogo "${catalogo.nombre}"? Esta acción no se puede deshacer.`)) return;
    loading.value = true;
    errorMessage.value = '';
    try {
        const res = await window.axios.delete(
            route('admin.configuracion.catalogos.destroy', { id: catalogo.id }),
        );
        successMessage.value = res.data?.message || 'Catálogo eliminado correctamente.';
        selectedCatalogo.value = null;
        valores.value = [];
        await fetchCatalogos(false);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'No fue posible eliminar el catálogo.';
    } finally {
        loading.value = false;
    }
};

// --- CRUD: VALORES ---
const openCreateValorModal = () => {
    if (!selectedCatalogo.value) return;
    editingValorId.value = null;
    valorForm.catalogo_id = selectedCatalogo.value.id;
    valorForm.codigo = '';
    valorForm.valor = '';
    valorForm.is_active = true;
    valorErrors.value = {};
    valorModalOpen.value = true;
};

const openEditValorModal = (valor) => {
    editingValorId.value = valor.id;
    valorForm.catalogo_id = selectedCatalogo.value?.id || '';
    valorForm.codigo = valor.codigo;
    valorForm.valor = valor.valor;
    valorForm.is_active = Boolean(valor.is_active);
    valorErrors.value = {};
    valorModalOpen.value = true;
};

const closeValorModal = () => {
    valorModalOpen.value = false;
};

const submitValor = async () => {
    valorSaving.value = true;
    valorErrors.value = {};
    errorMessage.value = '';
    try {
        if (editingValorId.value) {
            await window.axios.put(
                route('admin.configuracion.catalogos.valores.update', { id: editingValorId.value }),
                { ...valorForm },
            );
            successMessage.value = 'Valor actualizado correctamente.';
        } else {
            await window.axios.post(
                route('admin.configuracion.catalogos.valores.store'),
                { ...valorForm },
            );
            successMessage.value = 'Valor creado correctamente.';
        }
        valorModalOpen.value = false;
        if (selectedCatalogo.value) {
            await fetchValores(selectedCatalogo.value.slug);
            const res = await window.axios.get(route('admin.configuracion.catalogos.index'));
            catalogos.value = res.data?.data || [];
        }
    } catch (error) {
        if (error?.response?.status === 422) {
            valorErrors.value = error.response.data.errors || {};
            if (error.response.data.message) {
                valorErrors.value = { ...valorErrors.value, form: [error.response.data.message] };
            }
            return;
        }
        errorMessage.value = error?.response?.data?.message || 'Error al guardar el valor.';
    } finally {
        valorSaving.value = false;
    }
};

const deleteValor = async (id) => {
    if (!confirm('¿Estás seguro de eliminar este valor del catálogo?')) return;
    loading.value = true;
    errorMessage.value = '';
    try {
        const res = await window.axios.delete(
            route('admin.configuracion.catalogos.valores.destroy', { id }),
        );
        successMessage.value = res.data?.message || 'Valor eliminado correctamente.';
        if (selectedCatalogo.value) {
            await fetchValores(selectedCatalogo.value.slug);
            const resCat = await window.axios.get(route('admin.configuracion.catalogos.index'));
            catalogos.value = resCat.data?.data || [];
        }
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'No fue posible eliminar el valor.';
    } finally {
        loading.value = false;
    }
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
                        <h1 class="text-lg font-bold text-[var(--maya-text-main)]">Gestión de Catálogos y Clasificaciones</h1>
                        <p class="text-xs text-[var(--maya-text-muted)]">
                            Administración centralizada de catálogos operativos y sus valores configurables.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <RefreshButton :loading="loading" @refresh="refreshData" />
                </div>
            </div>

            <!-- Banner de Advertencia -->
            <div class="rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-700/50 dark:bg-amber-950/20 px-4 py-3">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 text-amber-600 dark:text-amber-400" />
                    <p class="text-xs text-amber-900 dark:text-amber-200">
                        <strong>ADVERTENCIA:</strong> Los catálogos y sus valores regulan los estados y flujos de envíos y rutas. La eliminación de registros en uso está restringida por integridad referencial.
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
                
                <!-- ========================================== -->
                <!-- PANEL IZQUIERDO: LISTA DE CATÁLOGOS        -->
                <!-- ========================================== -->
                <div class="lg:col-span-4 xl:col-span-4 border-r border-[var(--maya-border)] flex flex-col bg-[var(--maya-bg-surface)]">
                    <!-- Cabecera de Catálogos con botón de Crear Catálogo -->
                    <div class="p-3.5 border-b border-[var(--maya-border)] space-y-3 bg-[var(--maya-bg-base)]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold uppercase tracking-wider text-[var(--maya-text-main)]">
                                    Catálogos
                                </span>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)] border border-[var(--maya-border)]">
                                    {{ filteredCatalogos.length }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--maya-primary)] px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] transition-colors"
                                title="Crear un nuevo catálogo"
                                @click="openCreateCatalogoModal"
                            >
                                <font-awesome-icon :icon="['fas', 'plus']" class="text-xs" />
                                Nuevo Catálogo
                            </button>
                        </div>

                        <!-- Buscador de catálogos -->
                        <div class="relative">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-[var(--maya-text-muted)]" />
                            <input
                                v-model="catalogSearch"
                                type="text"
                                placeholder="Buscar catálogo..."
                                class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] py-2.5 pl-9 pr-9 text-sm text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--maya-primary)]"
                            />
                            <button
                                v-if="catalogSearch"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-sm text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                                @click="catalogSearch = ''"
                            >
                                <font-awesome-icon :icon="['fas', 'xmark']" />
                            </button>
                        </div>
                    </div>

                    <!-- Lista de Catálogos con scroll propio -->
                    <div class="flex-1 overflow-y-auto divide-y divide-[var(--maya-border)] max-h-[580px] lg:max-h-[calc(100vh-270px)]">
                        <div v-if="loading && !catalogos.length" class="p-8 text-center text-sm text-[var(--maya-text-muted)]">
                            <font-awesome-icon :icon="['fas', 'spinner']" class="fa-spin text-lg mb-2" />
                            <p>Cargando catálogos...</p>
                        </div>

                        <div v-else-if="!filteredCatalogos.length" class="p-8 text-center">
                            <p class="text-sm text-[var(--maya-text-muted)]">No se encontraron catálogos coincidentes.</p>
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
                                <div class="flex items-center gap-1.5">
                                    <p class="text-sm font-semibold text-[var(--maya-text-main)] truncate group-hover:text-[var(--maya-primary)]">
                                        {{ catalogo.nombre }}
                                    </p>
                                    <span v-if="!catalogo.is_global" class="inline-flex text-[10px] px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 font-medium">
                                        Propio
                                    </span>
                                </div>
                                <p class="text-xs text-[var(--maya-text-muted)] font-mono truncate mt-0.5">
                                    {{ catalogo.slug }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span
                                    class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold shrink-0"
                                    :class="selectedCatalogo?.id === catalogo.id
                                        ? 'bg-[var(--maya-primary)] text-white'
                                        : 'bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)] border border-[var(--maya-border)]'"
                                >
                                    {{ catalogo.valores_count ?? 0 }}
                                </span>
                                <button
                                    type="button"
                                    class="opacity-0 group-hover:opacity-100 h-7 w-7 inline-flex items-center justify-center rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition-all"
                                    title="Eliminar catálogo"
                                    @click.stop="deleteCatalogo(catalogo)"
                                >
                                    <font-awesome-icon :icon="['fas', 'trash']" class="text-xs" />
                                </button>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- PANEL DERECHO: DETALLE Y VALORES          -->
                <!-- ========================================== -->
                <div class="lg:col-span-8 xl:col-span-8 flex flex-col p-5 bg-[var(--maya-bg-surface)] space-y-6">

                    <!-- SECCIÓN 2: VALORES DEL CATÁLOGO SELECCIONADO -->
                    <div class="flex-1 flex flex-col min-h-0">
                        <!-- Cabecera de Valores -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-[var(--maya-border)]">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-[var(--maya-text-main)]">
                                        Valores del Catálogo
                                    </h3>
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                                        {{ valores.length }} valores
                                    </span>
                                </div>
                                <p class="text-xs text-[var(--maya-text-muted)] mt-0.5">
                                    Opciones, códigos y estados asignados al catálogo {{ selectedCatalogo?.nombre || '' }}.
                                </p>
                            </div>

                            <button
                                v-if="selectedCatalogo"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-[var(--maya-primary)] px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] transition-colors"
                                @click="openCreateValorModal"
                            >
                                <font-awesome-icon :icon="['fas', 'plus']" />
                                Nuevo Valor
                            </button>
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
                                    <span class="font-mono text-xs font-bold text-[var(--maya-text-main)] bg-[var(--maya-hover-surface)] px-2 py-0.5 rounded border border-[var(--maya-border)]">
                                        {{ row.codigo }}
                                    </span>
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
                                            title="Editar valor"
                                            @click="openEditValorModal(row)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'pencil']" class="text-xs" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 dark:border-red-900/50 text-red-500 hover:border-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                                            title="Eliminar valor del catálogo"
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
        </div>

        <!-- ========================================== -->
        <!-- MODAL 1: CREAR / EDITAR CATÁLOGO           -->
        <!-- ========================================== -->
        <ModalForm
            :model-value="catalogoForm"
            :show="catalogoModalOpen"
            :title="editingCatalogoId ? 'Editar Catálogo' : 'Nuevo Catálogo'"
            :description="editingCatalogoId ? 'Modifica el nombre y descripción del catálogo.' : 'Registra un nuevo catálogo para clasificaciones operativas de la paquetería.'"
            :fields="catalogoFields"
            :errors="catalogoErrors"
            :loading="catalogoSaving"
            :submit-label="editingCatalogoId ? 'Actualizar Catálogo' : 'Crear Catálogo'"
            :columns="1"
            @update:model-value="Object.assign(catalogoForm, $event)"
            @close="closeCatalogoModal"
            @submit="submitCatalogo"
        />

        <!-- ========================================== -->
        <!-- MODAL 2: CREAR / EDITAR VALOR DEL CATÁLOGO -->
        <!-- ========================================== -->
        <ModalForm
            :model-value="valorForm"
            :show="valorModalOpen"
            :title="editingValorId ? 'Editar Valor del Catálogo' : 'Nuevo Valor del Catálogo'"
            :description="editingValorId ? `Modificando valor para el catálogo: ${selectedCatalogo?.nombre}` : `Agregando nuevo valor al catálogo: ${selectedCatalogo?.nombre}`"
            :fields="valorFields"
            :errors="valorErrors"
            :loading="valorSaving"
            :submit-label="editingValorId ? 'Actualizar Valor' : 'Crear Valor'"
            :columns="1"
            @update:model-value="Object.assign(valorForm, $event)"
            @close="closeValorModal"
            @submit="submitValor"
        />
    </AdminLayout>
</template>
