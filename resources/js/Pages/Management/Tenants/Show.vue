<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import { 
    faBuilding, 
    faUsers, 
    faBox, 
    faArrowLeft,
    faPlay,
    faPause
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
});

const toggleStatus = () => {
    const action = props.tenant.status === 'active' ? 'pausar' : 'activar';
    if (confirm(`¿Estás seguro de ${action} la paquetería "${props.tenant.name}"?`)) {
        router.patch(route('Management.tenants.toggle-status', props.tenant.id));
    }
};
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link 
                        :href="route('Management.tenants.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                    >
                        <FontAwesomeIcon :icon="faArrowLeft" class="w-5 h-5" />
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ tenant.name }}
                    </h1>
                </div>
                <button
                    @click="toggleStatus"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150"
                    :class="{
                        'bg-yellow-600 text-white hover:bg-yellow-700': tenant.status === 'active',
                        'bg-green-600 text-white hover:bg-green-700': tenant.status === 'paused'
                    }"
                >
                    <FontAwesomeIcon :icon="tenant.status === 'active' ? faPause : faPlay" class="mr-2" />
                    {{ tenant.status === 'active' ? 'Pausar' : 'Activar' }}
                </button>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <FontAwesomeIcon :icon="faUsers" class="w-6 h-6 text-blue-600 dark:text-blue-300" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuarios</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ tenant.users_count || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                            <FontAwesomeIcon :icon="faUsers" class="w-6 h-6 text-purple-600 dark:text-purple-300" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Clientes</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ tenant.clients_count || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900">
                            <FontAwesomeIcon :icon="faBox" class="w-6 h-6 text-orange-600 dark:text-orange-300" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Envíos</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ tenant.shipments_count || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Información General
                    </h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tenant.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug (Subdominio)</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tenant.slug }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email de Contacto</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tenant.contact_email || 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tenant.phone || 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dirección</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tenant.address || 'No especificada' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                            <dd class="mt-1">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': tenant.status === 'active',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': tenant.status === 'paused'
                                    }"
                                >
                                    {{ tenant.status === 'active' ? 'Activa' : 'Pausada' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ new Date(tenant.created_at).toLocaleDateString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Última Actualización</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ new Date(tenant.updated_at).toLocaleDateString() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </ManagementLayout>
</template>
