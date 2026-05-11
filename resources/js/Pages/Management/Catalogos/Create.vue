<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import InputLabel from '@/Components/input/InputLabel.vue';
import TextInput from '@/Components/input/TextInput.vue';
import InputError from '@/Components/input/InputError.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import { faArrowLeft, faPlus, faGlobe, faBuilding } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenants: Array,
});

const form = useForm({
    nombre: '',
    slug: '',
    is_global: true,
    tenant_id: '',
    is_active: true,
});

const isTenantCatalog = computed(() => !form.is_global);

const submit = () => {
    if (form.is_global) {
        form.tenant_id = '';
    }
    form.post(route('Management.catalogos.store'));
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
                    Nuevo Catálogo
                </h1>
            </div>

            <!-- Form -->
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
                                placeholder="Ej: Estados de Envío"
                            />
                            <InputError :message="form.errors.nombre" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="slug" value="Slug *" />
                            <TextInput
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="Ej: shipment-status"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Una vez creado, el slug no se puede modificar. Solo minúsculas, números y guiones.
                            </p>
                            <InputError :message="form.errors.slug" class="mt-2" />
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
                            <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                            Crear Catálogo
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </ManagementLayout>
</template>
