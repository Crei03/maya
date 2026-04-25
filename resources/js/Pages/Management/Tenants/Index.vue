<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { 
    faBuilding, 
    faPlay, 
    faPause, 
    faEdit, 
    faEye,
    faSearch
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    tenants: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const debouncedSearch = debounce(() => {
    router.get(route('Management.tenants.index'), {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status], debouncedSearch);

const toggleStatus = (tenant) => {
    if (confirm(`¿Estás seguro de ${tenant.status === 'active' ? 'pausar' : 'activar'} la paquetería "${tenant.name}"?`)) {
        router.patch(route('Management.tenants.toggle-status', tenant.id));
    }
};
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Gestión de Paqueterías
                </h1>
                <Link 
                    :href="route('Management.tenants.index')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <FontAwesomeIcon :icon="faBuilding" class="mr-2" />
                    Nueva Paquetería
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <FontAwesomeIcon :icon="faSearch" class="text-gray-400" />
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Buscar paqueterías..."
                            />
                        </div>
                    </div>
                    <select
                        v-model="status"
                        class="block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">Todos los estados</option>
                        <option value="active">Activas</option>
                        <option value="paused">Pausadas</option>
                    </select>
                </div>
            </div>

            <!-- Tenants Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paquetería</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contacto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creada</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <FontAwesomeIcon :icon="faBuilding" class="h-5 w-5 text-indigo-600 dark:text-indigo-300" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ tenant.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ tenant.slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ tenant.contact_email || 'N/A' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ tenant.phone || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': tenant.status === 'active',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': tenant.status === 'paused'
                                        }"
                                    >
                                        {{ tenant.status === 'active' ? 'Activa' : 'Pausada' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(tenant.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link 
                                            :href="route('Management.tenants.show', tenant.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            title="Ver detalles"
                                        >
                                            <FontAwesomeIcon :icon="faEye" />
                                        </Link>
                                        <button
                                            @click="toggleStatus(tenant)"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            :title="tenant.status === 'active' ? 'Pausar' : 'Activar'"
                                        >
                                            <FontAwesomeIcon :icon="tenant.status === 'active' ? faPause : faPlay" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tenants.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No se encontraron paqueterías.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700" v-if="tenants.links">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Mostrando {{ tenants.from }} a {{ tenants.to }} de {{ tenants.total }} resultados
                        </div>
                        <div class="flex space-x-2">
                            <Link
                                v-for="link in tenants.links"
                                :key="link.label"
                                :href="link.url || '#'")
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
