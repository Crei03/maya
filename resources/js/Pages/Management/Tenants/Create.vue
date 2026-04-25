<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import InputLabel from '@/Components/input/InputLabel.vue';
import TextInput from '@/Components/input/TextInput.vue';
import InputError from '@/Components/input/InputError.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import { 
    faArrowLeft,
    faPlus
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    slug: '',
    contact_email: '',
    phone: '',
    address: '',
});

const submit = () => {
    form.post(route('Management.tenants.store'));
};
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-4">
                <Link 
                    :href="route('Management.tenants.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    <FontAwesomeIcon :icon="faArrowLeft" class="w-5 h-5" />
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Nueva Paquetería
                </h1>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="name" value="Nombre de la Paquetería *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="Ej: Transportes Panamá"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="slug" value="Slug (Subdominio) *" />
                            <TextInput
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="Ej: transportes-panama"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Se usará como subdominio: slug.maya.app
                            </p>
                            <InputError :message="form.errors.slug" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="contact_email" value="Email de Contacto" />
                            <TextInput
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                class="mt-1 block w-full"
                                placeholder="admin@paqueteria.com"
                            />
                            <InputError :message="form.errors.contact_email" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="phone" value="Teléfono" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="+507 6000-0000"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="address" value="Dirección" />
                        <textarea
                            id="address"
                            v-model="form.address"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="3"
                            placeholder="Dirección completa de la paquetería"
                        ></textarea>
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <Link
                            :href="route('Management.tenants.index')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                            Crear Paquetería
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </ManagementLayout>
</template>
