<script setup>
import {
    computed,
    ref,
    watch,
} from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faUpload, faTimes, faImage } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    existingImages: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue', 'update:deletedImages']);

const MAX_IMAGES = 3;

const newFiles = ref([...props.modelValue]);
const deletedImageIds = ref([]);
const fileInput = ref(null);
const isDragOver = ref(false);

const totalImages = computed(() => {
    const existingCount = props.existingImages.filter(img => !deletedImageIds.value.includes(img.id)).length;
    return existingCount + newFiles.value.length;
});

const canAddMore = computed(() => totalImages.value < MAX_IMAGES);

const newPreviews = computed(() => {
    return newFiles.value.map(file => {
        if (file instanceof File) {
            return URL.createObjectURL(file);
        }
        return file;
    });
});

const existingToShow = computed(() => {
    return props.existingImages.filter(img => !deletedImageIds.value.includes(img.id));
});

const handleFiles = (fileList) => {
    if (!canAddMore.value) return;

    const files = Array.from(fileList);
    const availableSlots = MAX_IMAGES - totalImages.value;
    const toAdd = files.slice(0, availableSlots);

    newFiles.value = [...newFiles.value, ...toAdd];
    emit('update:modelValue', [...newFiles.value]);
};

const onFileSelect = (e) => {
    handleFiles(e.target.files);
    if (fileInput.value) fileInput.value.value = '';
};

const onDrop = (e) => {
    isDragOver.value = false;
    if (e.dataTransfer.files?.length) {
        handleFiles(e.dataTransfer.files);
    }
};

const removeNewFile = (index) => {
    const file = newFiles.value[index];
    if (file instanceof File && file.preview) {
        URL.revokeObjectURL(file.preview);
    }
    newFiles.value.splice(index, 1);
    emit('update:modelValue', [...newFiles.value]);
};

const removeExisting = (imageId) => {
    deletedImageIds.value = [...deletedImageIds.value, imageId];
    emit('update:deletedImages', [...deletedImageIds.value]);
};

const openFilePicker = () => {
    if (canAddMore.value) {
        fileInput.value?.click();
    }
};

watch(() => props.modelValue, (val) => {
    if (val && val.length === 0) {
        newFiles.value = [];
    }
});
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Imágenes del Post
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ totalImages }}/{{ MAX_IMAGES }} imágenes
            </span>
        </div>

        <div
            class="border-2 border-dashed rounded-lg p-4 transition-colors"
            :class="canAddMore
                ? 'border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500 bg-gray-50 dark:bg-gray-800/50 cursor-pointer'
                : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-60'"
            @dragover.prevent="isDragOver = true"
            @dragenter.prevent="isDragOver = true"
            @dragleave.prevent="isDragOver = false"
            @drop.prevent="onDrop"
            @click="openFilePicker"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                accept="image/*"
                class="hidden"
                @change="onFileSelect"
            />

            <div v-if="totalImages === 0" class="text-center py-8">
                <FontAwesomeIcon :icon="faUpload" class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Arrastra imágenes aquí o haz clic para seleccionar
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    JPG, PNG o WebP. Máximo 5 MB por imagen
                </p>
            </div>

            <div
                v-else
                :class="isDragOver ? 'ring-2 ring-indigo-400 rounded' : ''"
                class="grid grid-cols-3 gap-3"
                @click.stop
            >
                <div
                    v-for="(img, idx) in existingToShow"
                    :key="'existing-' + img.id"
                    class="relative aspect-video rounded-md overflow-hidden bg-gray-200 dark:bg-gray-700 group"
                >
                    <img
                        :src="img.path.startsWith('http') ? img.path : '/storage/' + img.path"
                        :alt="img.original_name"
                        class="w-full h-full object-cover"
                    />
                    <button
                        type="button"
                        class="absolute top-1 right-1 p-0.5 rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                        @click.stop="removeExisting(img.id)"
                        title="Eliminar"
                    >
                        <FontAwesomeIcon :icon="faTimes" class="w-3 h-3" />
                    </button>
                </div>

                <div
                    v-for="(preview, idx) in newPreviews"
                    :key="'new-' + idx"
                    class="relative aspect-video rounded-md overflow-hidden bg-gray-200 dark:bg-gray-700 group"
                >
                    <img
                        :src="preview"
                        alt="Nueva imagen"
                        class="w-full h-full object-cover"
                    />
                    <button
                        type="button"
                        class="absolute top-1 right-1 p-0.5 rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                        @click.stop="removeNewFile(idx)"
                        title="Eliminar"
                    >
                        <FontAwesomeIcon :icon="faTimes" class="w-3 h-3" />
                    </button>
                </div>

                <div
                    v-if="canAddMore && totalImages > 0"
                    class="aspect-video rounded-md border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center hover:border-indigo-400 dark:hover:border-indigo-500 cursor-pointer bg-gray-50 dark:bg-gray-800/50 transition-colors"
                    @click.stop="openFilePicker"
                >
                    <FontAwesomeIcon :icon="faImage" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                </div>
            </div>
        </div>
    </div>
</template>
