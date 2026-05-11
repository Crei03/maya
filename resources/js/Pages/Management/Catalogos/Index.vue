<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import { faPlus, faEdit, faTrash, faSearch, faGlobe, faBuilding } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    catalogos: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const debouncedSearch = debounce(() => {
    router.get(route('Management.catalogos.index'), {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, debouncedSearch);

const deleteCatalogo = (catalogo) => {
    if (!confirm(`¿Estás seguro de eliminar el catálogo "${catalogo.nombre}"?`)) return;

    router.delete(route('Management.catalogos.destroy', catalogo.id), {
        preserveScroll: true,
        onError: (errors) => {
            alert(errors.message || 'Error al eliminar el catálogo.');
        },
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
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Catálogos
                </h1>
                <Link
                    :href="route('Management.catalogos.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                    Nuevo Catálogo
                </Link>
            </div>

            <!-- Search -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <FontAwesomeIcon :icon="faSearch" class="text-gray-400" />
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Buscar catálogos por nombre..."
                    />
                </div>
            </div>

            <!-- Catalogos Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Slug</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ámbito</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creado por</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="catalogo in catalogos.data" :key="catalogo.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ catalogo.nombre }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ catalogo.valores_count }} valor(es)
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ catalogo.slug }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="catalogo.is_global
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                            : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'"
                                    >
                                        <FontAwesomeIcon :icon="catalogo.is_global ? faGlobe : faBuilding" class="mr-1" />
                                        {{ catalogo.is_global ? 'Global' : 'Tenant' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ catalogo.creador?.name || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(catalogo.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link
                                            :href="route('Management.catalogos.edit', catalogo.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            title="Editar"
                                        >
                                            <FontAwesomeIcon :icon="faEdit" />
                                        </Link>
                                        <button
                                            @click="deleteCatalogo(catalogo)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="Eliminar"
                                        >
                                            <FontAwesomeIcon :icon="faTrash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="catalogos.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No se encontraron catálogos.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700" v-if="catalogos.links">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Mostrando {{ catalogos.from }} a {{ catalogos.to }} de {{ catalogos.total }} resultados
                        </div>
                        <div class="flex space-x-2">
                            <Link
                                v-for="link in catalogos.links"
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
        </div>
    </ManagementLayout>
</template>
