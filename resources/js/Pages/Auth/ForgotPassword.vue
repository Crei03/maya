<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/input/InputError.vue';import InputLabel from '@/Components/input/InputLabel.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TextInput from '@/Components/input/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div class="space-y-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--maya-text-muted)]">Recuperación de cuenta</p>
                <h1 class="text-3xl font-semibold tracking-tight text-[var(--maya-text-main)] sm:text-4xl">Restablece tu contraseña</h1>
                <p class="max-w-xl text-sm leading-6 text-[var(--maya-text-muted)]">
                    Ingresa el correo vinculado a tu cuenta Maya y te enviaremos un enlace seguro para restablecerla.
                </p>
            </div>

            <div v-if="status" class="rounded-2xl bg-[var(--maya-success-alpha)] px-4 py-3 text-sm font-medium text-[var(--maya-success-dark)]">
                {{ status }}
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

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[var(--maya-text-muted)]">
                        Revisa tu correo para encontrar el enlace que te permitirá elegir una nueva contraseña.
                    </p>

                    <PrimaryButton
                        class="inline-flex w-full items-center justify-center rounded-full bg-[var(--maya-primary)] px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_rgba(0,166,215,0.22)] transition hover:bg-[var(--maya-primary-dark)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] disabled:opacity-60 sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Enviar enlace de restablecimiento
                    </PrimaryButton>
                </div>

                <p class="text-sm text-[var(--maya-text-muted)]">
                    ¿Recordaste tu contraseña?
                    <Link :href="route('login')" class="font-semibold text-[var(--maya-primary)] transition hover:text-[var(--maya-primary-dark)]">
                        Volver a iniciar sesión
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
