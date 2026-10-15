<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/input/InputError.vue';
import InputLabel from '@/Components/input/InputLabel.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TextInput from '@/Components/input/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const form = useForm({
    company_name: '',
    slug: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const slugify = (text) => {
    return (text || '')
        .toString()
        .toLowerCase()
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
};

watch(() => form.company_name, (newName) => {
    form.slug = slugify(newName);
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro de Paquetería" />

        <div class="space-y-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--maya-text-muted)]">Onboarding</p>
                <h1 class="text-3xl font-semibold tracking-tight text-[var(--maya-text-main)] sm:text-4xl">Registra tu paquetería</h1>
                <p class="max-w-xl text-sm leading-6 text-[var(--maya-text-muted)]">
                    Crea el espacio de trabajo para tu empresa y el acceso del administrador principal para comenzar a operar.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Sección Empresa / Tenant -->
                <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-5 space-y-4">
                    <div class="border-b border-[var(--maya-border)] pb-2">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--maya-primary)]">Datos de la Empresa</h2>
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="company_name" value="Nombre de la Empresa / Paquetería *" />

                        <TextInput
                            id="company_name"
                            type="text"
                            class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                            v-model="form.company_name"
                            required
                            autofocus
                            placeholder="Ej. Transportes del Istmo"
                        />

                        <InputError class="mt-2" :message="form.errors.company_name" />

                        <p class="mt-2 text-xs text-[var(--maya-text-muted)]">
                            Tu dirección de acceso será: <span class="font-medium text-[var(--maya-primary)]">https://{{ form.slug || 'tu-empresa' }}.maya-efnj.onrender.com</span>
                        </p>

                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                </div>

                <!-- Sección Administrador -->
                <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-5 space-y-4">
                    <div class="border-b border-[var(--maya-border)] pb-2">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--maya-primary)]">Administrador Principal</h2>
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="name" value="Nombre y apellido *" />

                        <TextInput
                            id="name"
                            type="text"
                            class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            placeholder="Ej. Carlos Rodríguez"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="email" value="Correo electrónico *" />

                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="admin@transportes.com"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <InputLabel for="password" value="Contraseña *" />

                            <TextInput
                                id="password"
                                type="password"
                                class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                            />

                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <InputLabel for="password_confirmation" value="Confirmar contraseña *" />

                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                            />

                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[var(--maya-text-muted)]">
                        ¿Ya estás registrado?
                        <Link :href="route('login')" class="font-semibold text-[var(--maya-primary)] transition hover:text-[var(--maya-primary-dark)]">
                            Inicia sesión
                        </Link>
                    </p>

                    <PrimaryButton
                        class="inline-flex w-full items-center justify-center rounded-full bg-[var(--maya-primary)] px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_rgba(0,166,215,0.22)] transition hover:bg-[var(--maya-primary-dark)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] disabled:opacity-60 sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Registrar Paquetería
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
