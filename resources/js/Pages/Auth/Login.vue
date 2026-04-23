<script setup>
import Checkbox from '@/Components/input/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TextInput from '@/Components/input/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="space-y-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--maya-text-muted)]">Acceso seguro</p>
                <h1 class="text-3xl font-semibold tracking-tight text-[var(--maya-text-main)] sm:text-4xl">Bienvenido de nuevo</h1>
                <p class="max-w-xl text-sm leading-6 text-[var(--maya-text-muted)]">
                    Inicia sesión para gestionar envíos, revisar incidencias y mantener tu flujo de despacho activo.
                </p>
            </div>

            <div v-if="status" class="rounded-2xl bg-[var(--maya-success-alpha)] px-4 py-3 text-sm font-medium text-[var(--maya-success-dark)]">
                {{ status }}
            </div>

            <div v-if="Object.keys(form.errors).length" class="rounded-2xl bg-[var(--maya-danger-alpha)] px-4 py-3 text-sm font-medium text-[var(--maya-danger-dark)]">
                    Revisa los campos resaltados a continuación.
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <InputLabel for="email" value="Correo electrónico" />

                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="password" value="Contraseña" />

                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <label class="inline-flex items-center gap-3 text-sm text-[var(--maya-text-muted)]">
                        <Checkbox name="remember" v-model:checked="form.remember" class="rounded-md border-[var(--maya-border)] text-[var(--maya-primary)] focus:ring-[var(--maya-primary)]" />
                        <span>Recuérdame</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-[var(--maya-primary)] transition hover:text-[var(--maya-primary-dark)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)]"
                    >
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <PrimaryButton
                    class="inline-flex w-full items-center justify-center rounded-full bg-[var(--maya-primary)] px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_rgba(0,166,215,0.22)] transition hover:bg-[var(--maya-primary-dark)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] disabled:opacity-60"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>

                <p class="text-sm text-[var(--maya-text-muted)]">
                    ¿Necesitas una cuenta?
                    <Link :href="route('register')" class="font-semibold text-[var(--maya-primary)] transition hover:text-[var(--maya-primary-dark)]">
                        Crea una
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
