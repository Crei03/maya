<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import { 
    faBuilding, 
    faPlay, 
    faPause, 
    faBox,
    faPlus
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentAudits: Array,
});
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Dashboard SaaS
                </h1>
                <Link 
                    :href="route('Management.tenants.index')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                    Nueva Paquetería
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Total Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900">
                            <FontAwesomeIcon :icon="faBuilding" class="w-6 h-6 text-indigo-600 dark:text-indigo-300" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Paqueterías</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_tenants }}</p>
                        </div>
                    </div>
                </div>

                <!-- Active Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                            <FontAwesomeIcon :icon="faPlay" class="w-6 h-6 text-green-600 dark:text-green-300" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Activas</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_tenants }}</p>
                        </div>
                    </div>
                </div>

                <!-- Paused Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <FontAwesomeIcon :icon="faPause" class="w-6 h-6 text-yellow-600 dark:text-yellow-300" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pausadas</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.paused_tenants }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tenants -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Paqueterías Recientes
                    </h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Slug</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creada</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="tenant in stats.recent_tenants" :key="tenant.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ tenant.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ tenant.slug }}
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
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </ManagementLayout>
</template>
