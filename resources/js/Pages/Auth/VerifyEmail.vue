<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificación de correo" />

        <div class="space-y-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--maya-text-muted)]">Verificación</p>
                <h1 class="text-3xl font-semibold tracking-tight text-[var(--maya-text-main)] sm:text-4xl">Verifica tu correo</h1>
                <p class="max-w-xl text-sm leading-6 text-[var(--maya-text-muted)]">
                    Enviamos un enlace de verificación a la dirección usada durante el registro. Confírmalo para activar la cuenta.
                </p>
            </div>

            <div v-if="verificationLinkSent" class="rounded-2xl bg-[var(--maya-success-alpha)] px-4 py-3 text-sm font-medium text-[var(--maya-success-dark)]">
                A new verification link has been sent to the email address you provided during registration.
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="rounded-2xl bg-[var(--maya-bg-base)] px-4 py-4 text-sm text-[var(--maya-text-muted)] dark:bg-[var(--maya-bg-base)]">
                    Si no ves el mensaje, revisa la carpeta de spam o vuelve a enviar el correo de verificación a continuación.
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <PrimaryButton
                        class="inline-flex w-full items-center justify-center rounded-full bg-[var(--maya-primary)] px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_rgba(0,166,215,0.22)] transition hover:bg-[var(--maya-primary-dark)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] disabled:opacity-60 sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Reenviar correo de verificación
                    </PrimaryButton>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-sm font-medium text-[var(--maya-text-muted)] transition hover:text-[var(--maya-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)]"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
