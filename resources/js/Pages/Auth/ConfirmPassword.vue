<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TextInput from '@/Components/input/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar contraseña" />

        <div class="space-y-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--maya-text-muted)]">Verificación de seguridad</p>
                <h1 class="text-3xl font-semibold tracking-tight text-[var(--maya-text-main)] sm:text-4xl">Confirma tu contraseña</h1>
                <p class="max-w-xl text-sm leading-6 text-[var(--maya-text-muted)]">
                    Esta área está protegida. Confirma tu contraseña para continuar con la acción solicitada.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <InputLabel for="password" value="Contraseña" />
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full rounded-2xl border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-3 text-[var(--maya-text-main)] shadow-sm placeholder:text-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)] dark:bg-[var(--maya-bg-base)] dark:text-[var(--maya-text-main)]"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex justify-end">
                    <PrimaryButton
                        class="inline-flex w-full items-center justify-center rounded-full bg-[var(--maya-primary)] px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_28px_rgba(0,166,215,0.22)] transition hover:bg-[var(--maya-primary-dark)] focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] disabled:opacity-60 sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Confirmar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
