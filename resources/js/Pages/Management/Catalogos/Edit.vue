<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import InputLabel from '@/Components/input/InputLabel.vue';
import TextInput from '@/Components/input/TextInput.vue';
import InputError from '@/Components/input/InputError.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { faArrowLeft, faSave, faGlobe, faBuilding, faPlus, faEdit, faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    catalogo: Object,
    tenants: Array,
});

const form = useForm({
    nombre: props.catalogo.nombre,
    is_global: Boolean(props.catalogo.is_global),
    tenant_id: props.catalogo.tenant_id || '',
    is_active: Boolean(props.catalogo.is_active),
});

const isTenantCatalog = computed(() => !form.is_global);

const submit = () => {
    if (form.is_global) {
        form.tenant_id = '';
    }
    form.put(route('Management.catalogos.update', props.catalogo.id));
};

// Valores management
const modalOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

const valorForm = ref({
    codigo: '',
    valor: '',
    is_global: true,
    tenant_id: '',
    is_active: true,
});

const isValorTenant = computed(() => !valorForm.value.is_global);

const resetValorForm = () => {
    valorForm.value = {
        codigo: '',
        valor: '',
        is_global: Boolean(props.catalogo.is_global),
        tenant_id: props.catalogo.tenant_id || '',
        is_active: true,
    };
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
        is_global: Boolean(valor.is_global),
        tenant_id: valor.tenant_id || '',
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

    const data = {
        ...valorForm.value,
        catalogo_id: props.catalogo.id,
    };
    if (data.is_global) {
        data.tenant_id = null;
    }

    if (editingId.value) {
        router.put(route('Management.catalogos.valores.update', {
            catalogo: props.catalogo.id,
            valor: editingId.value,
        }), data, {
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
            onError: (err) => { errors.value = err; },
            onFinish: () => { saving.value = false; },
        });
    } else {
        router.post(route('Management.catalogos.valores.store', {
            catalogo: props.catalogo.id,
        }), data, {
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
            onError: (err) => { errors.value = err; },
            onFinish: () => { saving.value = false; },
        });
    }
};

const deleteValor = (valor) => {
    if (!confirm(`¿Estás seguro de eliminar el valor "${valor.valor}"?`)) return;
    router.delete(route('Management.catalogos.valores.destroy', {
        catalogo: props.catalogo.id,
        valor: valor.id,
    }), { preserveScroll: true });
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
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Editar Catálogo: {{ catalogo.nombre }}
                </h1>
            </div>

            <!-- Edit Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="nombre" value="Nombre *" />
                            <TextInput
                                id="nombre"
                                v-model="form.nombre"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.nombre" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="slug" value="Slug" />
                            <input
                                id="slug"
                                :value="catalogo.slug"
                                type="text"
                                disabled
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed shadow-sm sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                <font-awesome-icon :icon="['fas', 'lock']" class="mr-1" />
                                El slug no se puede modificar después de crear el catálogo.
                            </p>
                        </div>
                    </div>

                    <!-- Ámbito -->
                    <div class="space-y-3">
                        <InputLabel value="Ámbito" />
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="form.is_global"
                                    :value="true"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                />
                                <FontAwesomeIcon :icon="faGlobe" class="text-blue-600" />
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Global</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="form.is_global"
                                    :value="false"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                />
                                <FontAwesomeIcon :icon="faBuilding" class="text-green-600" />
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a Paquetería</span>
                            </label>
                        </div>

                        <div v-if="isTenantCatalog" class="max-w-md">
                            <select
                                v-model="form.tenant_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Seleccione una paquetería...</option>
                                <option
                                    v-for="tenant in tenants"
                                    :key="tenant.id"
                                    :value="tenant.id"
                                >
                                    {{ tenant.name }} ({{ tenant.slug }})
                                </option>
                            </select>
                            <InputError :message="form.errors.tenant_id" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-end pb-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activo</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <Link
                            :href="route('Management.catalogos.index')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            <FontAwesomeIcon :icon="faSave" class="mr-2" />
                            Guardar Cambios
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Valores Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Valores del Catálogo
                    </h2>
                    <div class="flex items-center gap-2">
                        <span v-if="!form.is_global" class="text-xs text-green-600 dark:text-green-400">
                            <FontAwesomeIcon :icon="faBuilding" class="mr-1" />
                            Valores asignados a paquetería
                        </span>
                        <span v-else class="text-xs text-blue-600 dark:text-blue-400">
                            <FontAwesomeIcon :icon="faGlobe" class="mr-1" />
                            Valores globales
                        </span>
                        <button
                            @click="openCreateValor"
                            class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                        >
                            <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                            Nuevo Valor
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ámbito</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="valor in catalogo.valores || []"
                                :key="valor.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <td class="px-6 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ valor.codigo }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ valor.valor }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="valor.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ valor.is_active ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="valor.is_global ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                    >
                                        <FontAwesomeIcon :icon="valor.is_global ? faGlobe : faBuilding" class="mr-1" />
                                        {{ valor.is_global ? 'Global' : 'Tenant' }}
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
                            <tr v-if="!catalogo.valores?.length">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No hay valores registrados para este catálogo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Valor Modal -->
            <Modal :show="modalOpen" max-width="md" @close="closeModal">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ editingId ? 'Editar Valor' : 'Nuevo Valor' }}
                    </h3>

                    <div class="mt-5 space-y-4">
                        <!-- Código -->
                        <div>
                            <InputLabel for="valor-codigo" value="Código *" />
                            <input
                                id="valor-codigo"
                                v-model="valorForm.codigo"
                                type="text"
                                maxlength="3"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Máx. 3 caracteres"
                            />
                            <InputError :message="errors.codigo?.[0]" class="mt-1" />
                        </div>

                        <!-- Valor -->
                        <div>
                            <InputLabel for="valor-valor" value="Valor *" />
                            <input
                                id="valor-valor"
                                v-model="valorForm.valor"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Nombre del valor"
                            />
                            <InputError :message="errors.valor?.[0]" class="mt-1" />
                        </div>

                        <!-- Ámbito -->
                        <div class="space-y-2">
                            <InputLabel value="Ámbito" />
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="valorForm.is_global"
                                        :value="true"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <FontAwesomeIcon :icon="faGlobe" class="text-blue-600" />
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Global</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="valorForm.is_global"
                                        :value="false"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <FontAwesomeIcon :icon="faBuilding" class="text-green-600" />
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a Paquetería</span>
                                </label>
                            </div>

                            <div v-if="isValorTenant" class="max-w-md">
                                <select
                                    v-model="valorForm.tenant_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Seleccione una paquetería...</option>
                                    <option
                                        v-for="tenant in tenants"
                                        :key="tenant.id"
                                        :value="tenant.id"
                                    >
                                        {{ tenant.name }} ({{ tenant.slug }})
                                    </option>
                                </select>
                                <InputError :message="errors.tenant_id?.[0]" class="mt-1" />
                            </div>
                        </div>

                        <!-- Activo -->
                        <label class="flex items-center gap-3 cursor-pointer pt-2">
                            <input
                                type="checkbox"
                                v-model="valorForm.is_active"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activo</span>
                        </label>

                        <InputError :message="errors.form?.[0]" class="mt-1" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            @click="closeModal"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                            :disabled="saving"
                            @click="submitValor"
                        >
                            {{ saving ? 'Guardando...' : editingId ? 'Actualizar Valor' : 'Crear Valor' }}
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </ManagementLayout>
</template>
