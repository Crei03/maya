<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/input/InputError.vue';import InputLabel from '@/Components/input/InputLabel.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TextInput from '@/Components/input/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro" />

        <div class="space-y-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--maya-text-muted)]">Onboarding</p>
                <h1 class="text-3xl font-semibold tracking-tight text-[var(--maya-text-main)] sm:text-4xl">Crea tu cuenta</h1>
                <p class="max-w-xl text-sm leading-6 text-[var(--maya-text-muted)]">
                    Establece acceso seguro para tu equipo y mantén las operaciones en movimiento desde el primer inicio de sesión.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <InputLabel for="name" value="Nombre" />

                    <TextInput
                        id="name"
                        type="text"
                        class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="email" value="Correo electrónico" />

                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <InputLabel for="password" value="Contraseña" />

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
                        <InputLabel for="password_confirmation" value="Confirmar contraseña" />

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
                        Register
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
