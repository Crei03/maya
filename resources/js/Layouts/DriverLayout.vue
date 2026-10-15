<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme.js';

const page = usePage();
const { isDark, toggleTheme } = useTheme();

const user = computed(() => page.props.auth?.user || {});
const tenant = computed(() => page.props.current_tenant || null);
const flash = computed(() => page.props.flash || {});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex justify-center antialiased">
        <div class="w-full max-w-md min-h-screen bg-white dark:bg-slate-900 border-x border-slate-200/80 dark:border-slate-800/80 shadow-2xl flex flex-col relative">
            <!-- Header Top Bar -->
            <header class="sticky top-0 z-40 backdrop-blur-md bg-white/90 dark:bg-slate-900/90 border-b border-slate-200/80 dark:border-slate-800/80 px-4 py-3 flex items-center justify-between transition-colors">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white shadow-sm font-black text-sm">
                        M
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-sm tracking-tight text-slate-900 dark:text-white">
                                {{ tenant ? tenant.name : 'Maya Express' }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Ruta
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium truncate max-w-[150px]">
                            {{ user.name || 'Conductor' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Dark mode toggle -->
                    <button
                        @click="toggleTheme"
                        type="button"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Cambiar tema"
                    >
                        <font-awesome-icon :icon="isDark ? ['fas', 'sun'] : ['fas', 'moon']" class="text-xs" />
                    </button>

                    <!-- Logout button -->
                    <button
                        @click="logout"
                        type="button"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                        title="Cerrar sesión"
                    >
                        <font-awesome-icon :icon="['fas', 'right-from-bracket']" class="text-xs" />
                    </button>
                </div>
            </header>

            <!-- Flash Notification -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <div v-if="flash.success" class="mx-4 mt-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs font-medium flex items-center gap-2 shadow-sm">
                    <font-awesome-icon :icon="['fas', 'circle-check']" class="text-emerald-600 dark:text-emerald-400 text-sm" />
                    <span class="flex-1">{{ flash.success }}</span>
                </div>
            </transition>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 pb-12 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
