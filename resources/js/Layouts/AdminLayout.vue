<script setup lang="jsx">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme.js';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

const page = usePage();
const isSidebarOpen = ref(true);
const isMobileMenuOpen = ref(false);

// Usar el composable de tema global
const { isDark: isDarkMode, toggleTheme } = useTheme();

// Logout handler
const logout = () => {
    router.post(route('logout'));
};

// Mock admin user - será reemplazado con auth real en el futuro
const user = computed(() => {
    return page.props.auth?.user || {
        full_name: 'Administrador',
        email: 'admin@maya.com',
        role: 'admin',
        avatar_url: null
    };
});

const navigation = [
    { name: 'Dashboard', href: route('admin.dashboard'), icon: ['fas', 'house'], current: route().current('admin.dashboard') },
    { name: 'Asignación', href: route('admin.asignacion-transporte'), icon: ['fas', 'truck'], current: route().current('admin.asignacion-transporte') },
    { name: 'Conciliación', href: route('admin.conciliacion-cierre'), icon: ['fas', 'clipboard-check'], current: route().current('admin.conciliacion-cierre') },
    { name: 'Envíos', href: '#', icon: ['fas', 'box'], current: false },
    { name: 'Mensajeros', href: '#', icon: ['fas', 'users'], current: false },
    { name: 'Reportes', href: '#', icon: ['fas', 'chart-line'], current: false },
    { name: 'Configuración', href: '#', icon: ['fas', 'gear'], current: false },
];
</script>

<template>
    <div class="min-h-screen bg-[var(--maya-bg-base)]">
        <!-- Mobile sidebar overlay -->
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
            @click="isMobileMenuOpen = false"
        />

        <!-- Mobile sidebar -->
        <div
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-[var(--maya-bg-sidebar)] transform transition-transform duration-300 ease-in-out lg:hidden border-r border-[var(--maya-border)]',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Mobile sidebar content -->
            <div class="flex h-16 items-center justify-between px-6 border-b border-[var(--maya-border)]">
                <span class="text-xl font-bold text-[var(--maya-primary)]">MAYA</span>
                <button
                    @click="isMobileMenuOpen = false"
                    class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="mt-5 px-2">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        item.current
                            ? 'bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]'
                            : 'text-[var(--maya-text-muted)] hover:bg-[var(--maya-hover-surface)] hover:text-[var(--maya-text-main)]',
                        'group flex items-center px-2 py-2 text-sm font-medium rounded-md mb-1'
                    ]"
                >
                    <font-awesome-icon
                        :icon="item.icon"
                        :class="[
                            item.current ? 'text-[var(--maya-primary)]' : 'text-[var(--maya-text-muted)] group-hover:text-[var(--maya-primary)]',
                            'mr-3 text-lg flex-shrink-0'
                        ]"
                    />
                    {{ item.name }}
                </Link>
            </nav>
            <!-- Mobile sidebar footer -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-[var(--maya-border)]">
                <!-- Dark mode toggle mobile -->
                <button
                    @click="toggleTheme"
                    class="flex items-center w-full px-2 py-2 text-sm font-medium text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] rounded-md mb-2"
                >
                    <font-awesome-icon
                        :icon="isDarkMode ? ['fas', 'sun'] : ['fas', 'moon']"
                        class="mr-3 text-lg flex-shrink-0"
                    />
                    {{ isDarkMode ? 'Modo Claro' : 'Modo Oscuro' }}
                </button>
                <!-- Logout mobile -->
                <button
                    @click="logout"
                    class="flex items-center w-full px-2 py-2 text-sm font-medium text-[var(--maya-danger)] hover:bg-[var(--maya-danger-alpha)] rounded-md"
                >
                    <font-awesome-icon :icon="['fas', 'right-from-bracket']" class="mr-3 text-lg flex-shrink-0" />
                    Cerrar Sesión
                </button>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div
            :class="[
                'hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:flex-col transition-all duration-300',
                isSidebarOpen ? 'lg:w-64' : 'lg:w-20'
            ]"
        >
            <!-- Sidebar component -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-[var(--maya-border)] bg-[var(--maya-bg-sidebar)] px-6 pb-4">
                <div class="flex h-16 items-center justify-between">
                    <span
                        :class="[
                            'text-xl font-bold text-[var(--maya-primary)] transition-opacity duration-300',
                            isSidebarOpen ? 'opacity-100' : 'opacity-0 hidden'
                        ]"
                    >
                        MAYA Admin
                    </span>
                    <button
                        @click="isSidebarOpen = !isSidebarOpen"
                        class="p-1 rounded-lg text-[var(--maya-text-muted)] hover:bg-[var(--maya-hover-surface)] hover:text-[var(--maya-text-main)]"
                        :class="{ 'mx-auto': !isSidebarOpen }"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            :class="['h-5 w-5 transition-transform duration-300', isSidebarOpen ? '' : 'rotate-180']"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-1">
                        <li v-for="item in navigation" :key="item.name">
                            <Link
                                :href="item.href"
                                :class="[
                                    item.current
                                        ? 'bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]'
                                        : 'text-[var(--maya-text-muted)] hover:bg-[var(--maya-hover-surface)] hover:text-[var(--maya-text-main)]',
                                    'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                    !isSidebarOpen && 'justify-center'
                                ]"
                            >
                                <font-awesome-icon
                                    :icon="item.icon"
                                    :class="[
                                        item.current ? 'text-[var(--maya-primary)]' : 'text-[var(--maya-text-muted)] group-hover:text-[var(--maya-primary)]',
                                        'text-lg flex-shrink-0'
                                    ]"
                                />
                                <span
                                    :class="[
                                        'transition-all duration-300 whitespace-nowrap',
                                        isSidebarOpen ? 'opacity-100 max-w-[150px]' : 'opacity-0 max-w-0 overflow-hidden'
                                    ]"
                                >
                                    {{ item.name }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </nav>

                <!-- Sidebar footer -->
                <div class="mt-auto pt-4 border-t border-[var(--maya-border)]">
                    <!-- Dark mode toggle -->
                    <button
                        @click="toggleTheme"
                        :class="[
                            'flex items-center w-full rounded-md p-2 text-sm font-semibold text-[var(--maya-text-muted)] hover:bg-[var(--maya-hover-surface)] hover:text-[var(--maya-text-main)] transition-all duration-300',
                            !isSidebarOpen && 'justify-center'
                        ]"
                    >
                        <font-awesome-icon
                            :icon="isDarkMode ? ['fas', 'sun'] : ['fas', 'moon']"
                            class="text-lg flex-shrink-0"
                            :class="isSidebarOpen ? 'mr-3' : ''"
                        />
                        <span
                            :class="[
                                'transition-all duration-300 whitespace-nowrap',
                                isSidebarOpen ? 'opacity-100 max-w-[150px]' : 'opacity-0 max-w-0 overflow-hidden'
                            ]"
                        >
                            {{ isDarkMode ? 'Modo Claro' : 'Modo Oscuro' }}
                        </span>
                    </button>

                    <!-- Logout button -->
                    <button
                        @click="logout"
                        :class="[
                            'flex items-center w-full rounded-md p-2 text-sm font-semibold text-[var(--maya-danger)] hover:bg-[var(--maya-danger-alpha)] transition-all duration-300 mt-1',
                            !isSidebarOpen && 'justify-center'
                        ]"
                    >
                        <font-awesome-icon
                            :icon="['fas', 'right-from-bracket']"
                            class="text-lg flex-shrink-0"
                            :class="isSidebarOpen ? 'mr-3' : ''"
                        />
                        <span
                            :class="[
                                'transition-all duration-300 whitespace-nowrap',
                                isSidebarOpen ? 'opacity-100 max-w-[150px]' : 'opacity-0 max-w-0 overflow-hidden'
                            ]"
                        >
                            Cerrar Sesión
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div
            :class="[
                'transition-all duration-300',
                isSidebarOpen ? 'lg:pl-64' : 'lg:pl-20'
            ]"
        >
            <!-- Header -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-x-4 border-b border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-[var(--maya-text-muted)] lg:hidden hover:text-[var(--maya-text-main)]"
                    @click="isMobileMenuOpen = true"
                >
                    <span class="sr-only">Abrir menú</span>
                    <font-awesome-icon :icon="['fas', 'bars']" class="text-xl" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-[var(--maya-border)] lg:hidden" aria-hidden="true" />

                <!-- Page title -->
                <div class="flex flex-1 items-center gap-x-4 self-stretch lg:gap-x-6">
                    <h1 class="text-lg font-semibold text-[var(--maya-text-main)]">
                        {{ title }}
                    </h1>
                </div>

                <!-- Right side actions -->
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    <!-- Dark mode toggle (header) -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="-m-2.5 p-2.5 text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)] transition-colors"
                    >
                        <span class="sr-only">Cambiar tema</span>
                        <font-awesome-icon :icon="isDarkMode ? ['fas', 'sun'] : ['fas', 'moon']" class="text-lg" />
                    </button>

                    <!-- Notifications -->
                    <button type="button" class="-m-2.5 p-2.5 text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]">
                        <span class="sr-only">Ver notificaciones</span>
                        <font-awesome-icon :icon="['fas', 'bell']" class="text-xl" />
                    </button>

                    <!-- Separator -->
                    <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-[var(--maya-border)]" aria-hidden="true" />

                    <!-- Profile dropdown -->
                    <div class="flex items-center gap-x-4">
                        <img
                            :src="user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name)}&background=00A6D7&color=fff`"
                            alt=""
                            class="h-8 w-8 rounded-full bg-[var(--maya-bg-base)]"
                        />
                        <span class="hidden text-sm font-semibold leading-6 text-[var(--maya-text-main)] lg:block">
                            {{ user.full_name }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="py-6">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
