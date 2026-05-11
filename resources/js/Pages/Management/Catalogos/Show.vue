<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ModalForm from '@/Components/ModalForm.vue';
import { faArrowLeft, faGlobe, faBuilding, faPlus, faEdit, faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    catalogo: Object,
    valores: Object,
});

const columns = [
    { key: 'codigo', label: 'Código' },
    { key: 'valor', label: 'Valor' },
    { key: 'descripcion', label: 'Descripción' },
    { key: 'is_active', label: 'Activo' },
    { key: 'sort_order', label: 'Orden' },
    { key: 'ambito', label: 'Ámbito' },
    { key: 'actions', label: 'Acciones' },
];

const modalOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

const valorForm = ref({
    codigo: '',
    valor: '',
    descripcion: '',
    sort_order: 0,
    is_active: true,
});

const valorFormFields = computed(() => [
    { key: 'codigo', label: 'Código *', type: 'text', placeholder: 'Máx. 3 caracteres' },
    { key: 'valor', label: 'Valor *', type: 'text', placeholder: 'Nombre del valor' },
    { key: 'descripcion', label: 'Descripción', type: 'text', placeholder: 'Opcional' },
    { key: 'sort_order', label: 'Orden', type: 'number', placeholder: '0' },
    { key: 'is_active', label: 'Activo', type: 'switch' },
]);

const resetValorForm = () => {
    valorForm.value = { codigo: '', valor: '', descripcion: '', sort_order: 0, is_active: true };
    editingId.value = null;
    errors.value = {};
};

const openCreateValor = () => {
    resetValorForm();
    modalOpen.value = true;
};

const openEditValor = (valor) => {
    editingId.value = valor.id;
    valorForm.value = {
        codigo: valor.codigo,
        valor: valor.valor,
        descripcion: valor.descripcion || '',
        sort_order: valor.sort_order ?? 0,
        is_active: Boolean(valor.is_active),
    };
    errors.value = {};
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

const submitValor = () => {
    saving.value = true;
    errors.value = {};

    if (editingId.value) {
        router.put(route('Management.catalogos.valores.update', {
            catalogo: props.catalogo.id,
            valor: editingId.value,
        }), valorForm.value, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                saving.value = false;
            },
        });
    } else {
        router.post(route('Management.catalogos.valores.store', {
            catalogo: props.catalogo.id,
        }), valorForm.value, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                saving.value = false;
            },
        });
    }
};

const deleteValor = (valor) => {
    if (!confirm(`¿Estás seguro de eliminar el valor "${valor.valor}"?`)) return;

    router.delete(route('Management.catalogos.valores.destroy', {
        catalogo: props.catalogo.id,
        valor: valor.id,
    }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Warning Banner -->
            <div class="rounded-lg border border-yellow-300 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/20 px-4 py-3">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 text-yellow-600 dark:text-yellow-400" />
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>ADVERTENCIA:</strong> Cualquier modificación en este módulo de catálogos puede afectar el flujo del sistema. Asegúrate de tener autorización antes de realizar cambios.
                    </p>
                </div>
            </div>

            <!-- Header -->
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('Management.catalogos.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    <FontAwesomeIcon :icon="faArrowLeft" class="w-5 h-5" />
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ catalogo.nombre }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Slug: {{ catalogo.slug }}
                    </p>
                </div>
                <Link
                    :href="route('Management.catalogos.edit', catalogo.id)"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                >
                    <FontAwesomeIcon :icon="faEdit" class="mr-2" />
                    Editar Catálogo
                </Link>
            </div>

            <!-- Valores Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Valores del Catálogo
                    </h2>
                    <button
                        @click="openCreateValor"
                        class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                        Nuevo Valor
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Activo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Orden</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ámbito</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="valor in valores.data" :key="valor.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ valor.codigo }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ valor.valor }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">{{ valor.descripcion || '—' }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="valor.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'"
                                    >
                                        {{ valor.is_active ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">{{ valor.sort_order }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="valor.is_global ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                    >
                                        <FontAwesomeIcon :icon="valor.is_global ? faGlobe : faBuilding" class="mr-1" />
                                        {{ valor.is_global ? 'Global' : valor.tenant?.name || 'Tenant' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button @click="openEditValor(valor)" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                            <FontAwesomeIcon :icon="faEdit" />
                                        </button>
                                        <button @click="deleteValor(valor)" class="text-red-600 hover:text-red-900" title="Eliminar">
                                            <FontAwesomeIcon :icon="faTrash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!valores.data.length">
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No hay valores registrados para este catálogo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700" v-if="valores.links">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Mostrando {{ valores.from }} a {{ valores.to }} de {{ valores.total }} resultados
                        </div>
                        <div class="flex space-x-2">
                            <Link
                                v-for="link in valores.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="px-3 py-1 rounded text-sm"
                                :class="{
                                    'bg-indigo-600 text-white': link.active,
                                    'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600': !link.active && link.url,
                                    'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600': !link.url
                                }"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Valor Modal -->
            <ModalForm
                :show="modalOpen"
                :title="editingId ? 'Editar Valor' : 'Nuevo Valor'"
                :fields="valorFormFields"
                :model-value="valorForm"
                :errors="errors"
                :loading="saving"
                :submit-label="editingId ? 'Actualizar Valor' : 'Crear Valor'"
                :columns="1"
                @update:model-value="Object.assign(valorForm, $event)"
                @close="closeModal"
                @submit="submitValor"
            />
        </div>
    </ManagementLayout>
</template>
