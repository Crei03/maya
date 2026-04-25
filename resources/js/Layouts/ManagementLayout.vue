<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    faGauge, 
    faBuilding, 
    faClipboardList, 
    faCog,
    faBars,
    faXmark
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const showingNavigationDropdown = ref(false);
const user = usePage().props.auth.user;

const navigation = [
    { name: 'Dashboard', href: route('Management.dashboard'), icon: faGauge, active: route().current('Management.dashboard') },
    { name: 'Paqueterías', href: route('Management.tenants.index'), icon: faBuilding, active: route().current('Management.tenants.*') },
    { name: 'Auditoría', href: route('Management.audit-logs.index'), icon: faClipboardList, active: route().current('Management.audit-logs.*') },
];
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Sidebar -->
        <aside 
            class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700"
            :class="{ '-translate-x-full': !showingNavigationDropdown, 'translate-x-0': showingNavigationDropdown }"
        >
            <div class="h-full px-3 py-4 overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <Link href="/" class="flex items-center">
                        <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">
                            MAYA SaaS
                        </span>
                    </Link>
                    <button @click="showingNavigationDropdown = false" class="md:hidden">
                        <FontAwesomeIcon :icon="faXmark" class="w-6 h-6 text-gray-500" />
                    </button>
                </div>

                <ul class="space-y-2 font-medium">
                    <li v-for="item in navigation" :key="item.name">
                        <Link 
                            :href="item.href"
                            class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                            :class="{ 'bg-gray-100 dark:bg-gray-700': item.active }"
                        >
                            <FontAwesomeIcon :icon="item.icon" class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" />
                            <span class="ml-3">{{ item.name }}</span>
                        </Link>
                    </li>
                </ul>

                <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ user.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Super Admin</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="md:ml-64">
            <!-- Top Bar -->
            <nav class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-4 py-3">
                    <button 
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="md:hidden inline-flex items-center p-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    >
                        <FontAwesomeIcon :icon="faBars" class="w-6 h-6" />
                    </button>
                </div>
            </nav>

            <!-- Page Content -->
            <main class="p-4">
                <slot />
            </main>
        </div>
    </div>
</template>
