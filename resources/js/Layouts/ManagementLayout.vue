<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    faBuilding, 
    faClipboardList, 
    faBars,
    faXmark,
    faSun,
    faMoon,
    faRightFromBracket,
    faBell,
    faHouse
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useTheme } from '@/Composables/useTheme.js';

const isMobileMenuOpen = ref(false);
const user = usePage().props.auth.user;
const { isDark: isDarkMode, toggleTheme } = useTheme();

const logout = () => {
    router.post(route('logout'));
};

const navigation = [
    { name: 'Dashboard', href: route('Management.dashboard'), icon: faHouse, active: route().current('Management.dashboard') },
    { name: 'Paqueterías', href: route('Management.tenants.index'), icon: faBuilding, active: route().current('Management.tenants.*') },
    { name: 'Auditoría', href: route('Management.audit-logs.index'), icon: faClipboardList, active: route().current('Management.audit-logs.*') },
];
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Mobile sidebar overlay -->
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
            @click="isMobileMenuOpen = false"
        />

        <!-- Mobile sidebar -->
        <div
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 transform transition-transform duration-300 ease-in-out lg:hidden border-r border-gray-200 dark:border-gray-700',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="flex h-16 items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
                <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">MAYA</span>
                <button
                    @click="isMobileMenuOpen = false"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <FontAwesomeIcon :icon="faXmark" class="w-6 h-6" />
                </button>
            </div>
            <nav class="mt-5 px-2">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        item.active
                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                        'group flex items-center px-2 py-2 text-sm font-medium rounded-md mb-1'
                    ]"
                >
                    <FontAwesomeIcon
                        :icon="item.icon"
                        :class="[
                            item.active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300',
                            'mr-3 text-lg flex-shrink-0'
                        ]"
                    />
                    {{ item.name }}
                </Link>
            </nav>
            <!-- Mobile sidebar footer -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
                <!-- Dark mode toggle mobile -->
                <button
                    @click="toggleTheme"
                    class="flex items-center w-full px-2 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md mb-2"
                >
                    <FontAwesomeIcon :icon="isDarkMode ? faSun : faMoon" class="mr-3 text-lg flex-shrink-0" />
                    {{ isDarkMode ? 'Modo Claro' : 'Modo Oscuro' }}
                </button>
                <!-- Logout mobile -->
                <button
                    @click="logout"
                    class="flex items-center w-full px-2 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md"
                >
                    <FontAwesomeIcon :icon="faRightFromBracket" class="mr-3 text-lg flex-shrink-0" />
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-64 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 pb-4">
                <div class="flex h-16 items-center">
                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                        MAYA
                    </span>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-1">
                        <li v-for="item in navigation" :key="item.name">
                            <Link
                                :href="item.href"
                                :class="[
                                    item.active
                                        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                    'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6'
                                ]"
                            >
                                <FontAwesomeIcon
                                    :icon="item.icon"
                                    :class="[
                                        item.active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300',
                                        'text-lg flex-shrink-0'
                                    ]"
                                />
                                {{ item.name }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <!-- Sidebar footer -->
                <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                    <!-- Dark mode toggle desktop -->
                    <button
                        @click="toggleTheme"
                        class="flex items-center w-full rounded-md p-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all duration-300 mb-1"
                    >
                        <FontAwesomeIcon :icon="isDarkMode ? faSun : faMoon" class="text-lg flex-shrink-0 mr-3" />
                        {{ isDarkMode ? 'Modo Claro' : 'Modo Oscuro' }}
                    </button>
                    <!-- Logout button -->
                    <button
                        @click="logout"
                        class="flex items-center w-full rounded-md p-2 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-300 mt-1"
                    >
                        <FontAwesomeIcon :icon="faRightFromBracket" class="text-lg flex-shrink-0 mr-3" />
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top Bar -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-x-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-500 dark:text-gray-400 lg:hidden hover:text-gray-700 dark:hover:text-gray-200"
                    @click="isMobileMenuOpen = true"
                >
                    <span class="sr-only">Abrir menú</span>
                    <FontAwesomeIcon :icon="faBars" class="text-xl" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true" />

                <!-- Page title -->
                <div class="flex flex-1 items-center gap-x-4 self-stretch lg:gap-x-6">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Panel de Administración
                    </h1>
                </div>

                <!-- Right side actions -->
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    <!-- Dark mode toggle (header) -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="-m-2.5 p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                    >
                        <span class="sr-only">Cambiar tema</span>
                        <FontAwesomeIcon :icon="isDarkMode ? faSun : faMoon" class="text-xl" />
                    </button>

                    <!-- Notifications -->
                    <button type="button" class="-m-2.5 p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <span class="sr-only">Ver notificaciones</span>
                        <FontAwesomeIcon :icon="faBell" class="text-xl" />
                    </button>

                    <!-- User avatar -->
                    <div class="flex items-center gap-x-4">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                            {{ user?.name?.charAt(0) || 'S' }}
                        </div>
                        <span class="hidden text-sm font-semibold leading-6 text-gray-900 dark:text-white lg:block">
                            {{ user?.name || 'Super Admin' }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="py-6">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
