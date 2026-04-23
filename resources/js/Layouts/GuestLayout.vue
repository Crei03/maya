<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-[var(--maya-bg-base)] text-[var(--maya-text-main)]">
        <div class="pointer-events-none absolute -left-20 top-8 h-72 w-72 rounded-full bg-[color:rgba(0,166,215,0.16)] blur-3xl dark:bg-[color:rgba(0,166,215,0.20)]"></div>
        <div class="pointer-events-none absolute bottom-[-6rem] right-[-4rem] h-80 w-80 rounded-full bg-[color:rgba(3,31,65,0.12)] blur-3xl dark:bg-[color:rgba(255,255,255,0.08)]"></div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid w-full gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <section class="hidden overflow-hidden rounded-[2rem] bg-[linear-gradient(160deg,var(--maya-primary),var(--maya-primary-dark))] p-8 text-white shadow-[0_32px_90px_rgba(15,23,42,0.18)] lg:flex lg:flex-col lg:justify-between">
                    <div class="space-y-8">
                        <Link href="/" class="inline-flex items-center gap-3">
                            <span class="rounded-2xl bg-white/12 p-3 backdrop-blur-md">
                                <ApplicationLogo class="h-10 w-10 fill-current text-white" />
                            </span>
                            <span>
                                <span class="block text-xs uppercase tracking-[0.3em] text-white/70">Maya</span>
                                <span class="block text-lg font-semibold">Control</span>
                            </span>
                        </Link>

                        <div class="space-y-4">
                            <p class="max-w-lg text-4xl font-semibold leading-tight">Todo bajo control, en un solo lugar.</p>
                            <p class="max-w-md text-sm leading-6 text-white/80">Simplificamos la complejidad logística para que puedas enfocarte en lo que importa.</p>
                        </div>
                    </div>

                    <div class="grid gap-3 text-sm text-white/85 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.24em] text-white/60">TRAZABILIDAD</p>
                            <p class="mt-2 font-medium">Datos en tiempo real, sin retrasos.</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.24em] text-white/60">ÁGIL</p>
                            <p class="mt-2 font-medium">Acceso instantáneo a tus paneles personalizados.</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.24em] text-white/60">SOPORTE</p>
                            <p class="mt-2 font-medium">Asistencia técnica a un clic de distancia.</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[2rem] bg-[var(--maya-bg-surface)] px-5 py-6 shadow-[0_30px_80px_rgba(15,23,42,0.12)] ring-1 ring-[color:rgba(148,163,184,0.18)] sm:px-8 sm:py-8 lg:px-10 lg:py-10 dark:ring-[color:rgba(148,163,184,0.12)]">
                    <div class="mb-8 flex items-center justify-between gap-4 lg:hidden">
                        <Link href="/" class="inline-flex items-center gap-3">
                            <span class="rounded-2xl bg-[var(--maya-primary-alpha)] p-3">
                                <ApplicationLogo class="h-8 w-8 fill-current text-[var(--maya-primary)]" />
                            </span>
                            <span>
                                <span class="block text-xs uppercase tracking-[0.3em] text-[var(--maya-text-muted)]">Maya</span>
                                <span class="block text-sm font-semibold text-[var(--maya-text-main)]">Control</span>
                            </span>
                        </Link>
                    </div>

                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
