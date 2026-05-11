<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import {
    faNewspaper,
    faPlay,
    faPause,
    faPencil,
    faEye,
    faTrash,
    faSearch,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    posts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const debouncedSearch = debounce(() => {
    router.get(route('Management.blog.index'), {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status], debouncedSearch);

const togglePublish = (post) => {
    if (confirm(`¿Estás seguro de ${post.is_published ? 'ocultar' : 'publicar'} el post "${post.title}"?`)) {
        router.post(route('Management.blog.publish', post.id));
    }
};

const deletePost = (post) => {
    if (confirm(`¿Estás seguro de eliminar el post "${post.title}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('Management.blog.destroy', post.id));
    }
};

const getStatusLabel = (isPublished) => {
    return isPublished ? 'Publicado' : 'Borrador';
};

const getStatusClass = (isPublished) => {
    return isPublished
        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString();
};
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Gestión de Blog
                </h1>
                <Link
                    :href="route('Management.blog.create')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <FontAwesomeIcon :icon="faNewspaper" class="mr-2" />
                    Nuevo Post
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <FontAwesomeIcon :icon="faSearch" class="text-gray-400" />
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Buscar posts..."
                            />
                        </div>
                    </div>
                    <select
                        v-model="status"
                        class="block w-48 pl-3 pr-10 py-2 text-base border-[var(--maya-border)] focus:outline-none focus:ring-[var(--maya-primary)] focus:border-[var(--maya-primary)] sm:text-sm rounded-md bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)]"
                    >
                        <option value="">Todos los estados</option>
                        <option value="published">Publicados</option>
                        <option value="draft">Borradores</option>
                    </select>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Publicado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actualizado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Autor</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <FontAwesomeIcon :icon="faNewspaper" class="h-5 w-5 text-indigo-600 dark:text-indigo-300" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ post.title }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ post.slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="getStatusClass(post.is_published)"
                                    >
                                        {{ getStatusLabel(post.is_published) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(post.published_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(post.updated_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ post.author || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link
                                            :href="route('Management.blog.edit', post.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            title="Editar"
                                        >
                                            <FontAwesomeIcon :icon="faPencil" />
                                        </Link>
                                        <button
                                            @click="togglePublish(post)"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            :title="post.is_published ? 'Ocultar' : 'Publicar'"
                                        >
                                            <FontAwesomeIcon :icon="post.is_published ? faPause : faPlay" />
                                        </button>
                                        <button
                                            @click="deletePost(post)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="Eliminar"
                                        >
                                            <FontAwesomeIcon :icon="faTrash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="posts.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No se encontraron posts.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700" v-if="posts.links">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Mostrando {{ posts.from }} a {{ posts.to }} de {{ posts.total }} resultados
                        </div>
                        <div class="flex space-x-2">
                            <Link
                                v-for="link in posts.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="px-3 py-1 rounded text-sm"
                                :class="{
                                    'bg-indigo-600 text-white': link.active,
                                    'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600': !link.active && link.url,
                                    'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600': !link.url
                                }"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ManagementLayout>
</template>
