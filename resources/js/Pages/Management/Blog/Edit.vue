<script setup>
import ManagementLayout from '@/Layouts/ManagementLayout.vue';
import InputLabel from '@/Components/input/InputLabel.vue';
import TextInput from '@/Components/input/TextInput.vue';
import InputError from '@/Components/input/InputError.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TiptapEditor from '@/Components/TiptapEditor.vue';
import BlogImageUploader from '@/Components/BlogImageUploader.vue';
import {
    faArrowLeft,
    faMagicWandSparkles,
    faSave,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    post: Object,
});

const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    author: props.post.author || '',
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
    meta_keywords: props.post.meta_keywords || '',
    og_title: props.post.og_title || '',
    og_description: props.post.og_description || '',
    og_image: props.post.og_image || '',
    canonical_url: props.post.canonical_url || '',
    structured_data: props.post.structured_data || null,
    reading_time: props.post.reading_time || null,
    is_published: props.post.is_published,
    images: [],
    deleted_image_ids: [],
});

const analyzing = ref(false);
const seoReadingTime = ref(props.post.reading_time ?? null);

const normalizeSlug = (text) => {
    const charMap = {
        'á':'a', 'é':'e', 'í':'i', 'ó':'o', 'ú':'u', 'ü':'u', 'ñ':'n',
        'Á':'a', 'É':'e', 'Í':'i', 'Ó':'o', 'Ú':'u', 'Ü':'u', 'Ñ':'n',
    };
    return text
        .split('')
        .map(c => charMap[c] || c)
        .join('')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
};

const onTitleInput = () => {
    if (form.title) {
        form.slug = normalizeSlug(form.title);
    }
};

const analyzeSeo = async () => {
    if (!form.title || !form.content) return;

    analyzing.value = true;
    try {
        const response = await window.axios.post(
            route('Management.blog.analyze-seo'),
            { title: form.title, content: form.content }
        );

        form.meta_title = response.data.meta_title || '';
        form.meta_description = response.data.meta_description || '';
        form.meta_keywords = response.data.meta_keywords || '';
        form.og_title = response.data.og_title || '';
        form.og_description = response.data.og_description || '';
        form.reading_time = response.data.reading_time || null;
        form.structured_data = response.data.structured_data || null;
        seoReadingTime.value = response.data.reading_time || null;
    } catch (e) {
        console.error('SEO analysis failed', e);
    } finally {
        analyzing.value = false;
    }
};

const submit = () => {
    form.transform(data => ({
        ...data,
        structured_data: data.structured_data ? JSON.stringify(data.structured_data) : null,
    })).patch(route('Management.blog.update', props.post.id));
};
</script>

<template>
    <ManagementLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('Management.blog.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    <FontAwesomeIcon :icon="faArrowLeft" class="w-5 h-5" />
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Editar Post
                </h1>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="title" value="Título *" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                @input="onTitleInput"
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="slug" value="Slug (auto-generado) *" />
                            <TextInput
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                                readonly
                                required
                            />
                            <InputError :message="form.errors.slug" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="excerpt" value="Extracto" />
                        <textarea
                            id="excerpt"
                            v-model="form.excerpt"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="2"
                        ></textarea>
                        <InputError :message="form.errors.excerpt" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="content" value="Contenido *" />
                        <TiptapEditor v-model="form.content" />
                        <InputError :message="form.errors.content" class="mt-2" />
                    </div>

                    <div>
                        <BlogImageUploader
                            v-model="form.images"
                            :existing-images="props.post.images || []"
                            @update:deleted-images="(ids) => form.deleted_image_ids = ids"
                        />
                        <InputError :message="form.errors.images" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="author" value="Autor" />
                            <TextInput
                                id="author"
                                v-model="form.author"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.author" class="mt-2" />
                        </div>
                    </div>

                    <!-- SEO Fields -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                SEO
                            </h3>
                            <button
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-md transition-colors"
                                :disabled="analyzing || !form.title || !form.content"
                                @click="analyzeSeo"
                            >
                                <FontAwesomeIcon :icon="faMagicWandSparkles" class="mr-1.5 w-3.5 h-3.5" :class="{ 'animate-spin': analyzing }" />
                                {{ analyzing ? 'Analizando...' : 'Analizar SEO' }}
                            </button>
                        </div>

                        <div v-if="seoReadingTime !== null" class="mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-md">
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                Tiempo estimado de lectura: <strong>{{ seoReadingTime }} minuto{{ seoReadingTime !== 1 ? 's' : '' }}</strong>
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <InputLabel for="meta_title" value="Meta Título" />
                                <TextInput
                                    id="meta_title"
                                    v-model="form.meta_title"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.meta_title" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="meta_description" value="Meta Descripción" />
                                <textarea
                                    id="meta_description"
                                    v-model="form.meta_description"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    rows="2"
                                ></textarea>
                                <InputError :message="form.errors.meta_description" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="meta_keywords" value="Meta Palabras Clave" />
                                <TextInput
                                    id="meta_keywords"
                                    v-model="form.meta_keywords"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.meta_keywords" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="og_title" value="OG Título" />
                                <TextInput
                                    id="og_title"
                                    v-model="form.og_title"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.og_title" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="og_description" value="OG Descripción" />
                                <textarea
                                    id="og_description"
                                    v-model="form.og_description"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    rows="2"
                                ></textarea>
                                <InputError :message="form.errors.og_description" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="og_image" value="OG Imagen URL" />
                                <TextInput
                                    id="og_image"
                                    v-model="form.og_image"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.og_image" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="canonical_url" value="Canonical URL" />
                                <TextInput
                                    id="canonical_url"
                                    v-model="form.canonical_url"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.canonical_url" class="mt-2" />
                            </div>
                        </div>

                    </div>

                    <div class="flex items-center">
                        <input
                            id="is_published"
                            v-model="form.is_published"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <InputLabel for="is_published" value="Publicado" class="ml-2" />
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <Link
                            :href="route('Management.blog.index')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            <FontAwesomeIcon :icon="faSave" class="mr-2" />
                            Guardar Cambios
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </ManagementLayout>
</template>
