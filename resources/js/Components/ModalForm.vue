<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/input/InputError.vue';
import InputLabel from '@/Components/input/InputLabel.vue';
import TextInput from '@/Components/input/TextInput.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    fields: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    submitLabel: {
        type: String,
        default: 'Guardar',
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    columns: {
        type: Number,
        default: 2,
    },
});

const emit = defineEmits(['close', 'submit', 'update:modelValue']);

const updateField = (key, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
};

const normalizedFields = computed(() => props.fields || []);

const gridClass = computed(() => {
    return props.columns === 1 ? 'grid-cols-1' : 'grid-cols-1 md:grid-cols-2';
});

// --- Leaflet map state ---
let mapContainerEl = null;
let mapInstance = null;
let markerInstance = null;

const setMapContainer = (el) => {
    mapContainerEl = el;
};

const mapField = computed(() => {
    return normalizedFields.value.find((f) => f.type === 'map');
});

const initMap = async () => {
    if (!mapContainerEl || !mapField.value) return;
    
    // Prevent double initialization
    if (mapInstance) {
        destroyMap();
    }

    // Ensure container is a real DOM element with dimensions
    if (!(mapContainerEl instanceof HTMLElement)) {
        console.warn('Map container is not an HTMLElement', mapContainerEl);
        return;
    }

    const rect = mapContainerEl.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) {
        console.warn('Map container has no dimensions, waiting...');
        await new Promise((resolve) => setTimeout(resolve, 200));
    }

    const field = mapField.value;
    const defaultCenter = field.defaultCenter ?? [-34.6037, -58.3816];
    const defaultZoom = field.defaultZoom ?? 13;

    // Fix Leaflet default marker icon paths (Vite bundling issue)
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    mapInstance = L.map(mapContainerEl).setView(defaultCenter, defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(mapInstance);

    // If existing coords (edit mode), place marker
    const existingCoords = props.modelValue[field.key];
    if (existingCoords && existingCoords.lat && existingCoords.lng) {
        markerInstance = L.marker([existingCoords.lat, existingCoords.lng]).addTo(mapInstance);
        mapInstance.setView([existingCoords.lat, existingCoords.lng], defaultZoom);
    }

    // Click handler: place/move marker and update form
    mapInstance.on('click', (e) => {
        const { lat, lng } = e.latlng;

        if (markerInstance) {
            markerInstance.setLatLng([lat, lng]);
        } else {
            markerInstance = L.marker([lat, lng]).addTo(mapInstance);
        }

        updateField(field.key, { lat, lng });
    });

    // Final invalidateSize
    mapInstance.invalidateSize();
    console.log('Map initialized successfully');
};

const destroyMap = () => {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        markerInstance = null;
    }
    mapContainerEl = null;
};

// Watch for modal open/close to manage map lifecycle
watch(
    () => props.show,
    async (newVal) => {
        if (newVal && mapField.value) {
            // Wait for modal transition (300ms) + buffer before init
            await new Promise((resolve) => setTimeout(resolve, 350));
            await initMap();
        } else {
            // Clean up map when modal closes
            destroyMap();
        }
    },
);

onMounted(async () => {
    if (props.show && mapField.value) {
        await nextTick();
        await initMap();
    }
});

onUnmounted(() => {
    destroyMap();
});
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-sm text-[var(--maya-text-muted)]">
                {{ description }}
            </p>

            <div class="mt-5 grid gap-4" :class="gridClass">
                <div
                    v-for="field in normalizedFields"
                    :key="field.key"
                    class="min-w-0"
                    :style="field.colSpan ? { gridColumn: `span ${field.colSpan} / span ${field.colSpan}` } : undefined"
                >
                    <InputLabel v-if="field.type !== 'switch'" :value="field.label" />

                    <template v-if="field.type === 'select'">
                        <select
                            class="mt-1 block w-full rounded-md border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:ring-[var(--maya-primary)]"
                            :value="modelValue[field.key] ?? ''"
                            @change="updateField(field.key, field.multiple ? Array.from($event.target.selectedOptions).map((option) => option.value) : ($event.target.value === '' ? null : (field.valueType === 'number' ? Number($event.target.value) : $event.target.value)))"
                        >
                            <option v-if="field.placeholder !== false" value="">
                                {{ field.placeholder || 'Seleccionar opción' }}
                            </option>
                            <option
                                v-for="option in field.options || []"
                                :key="option[field.optionValue || 'id']"
                                :value="option[field.optionValue || 'id']"
                            >
                                {{ option[field.optionLabel || 'valor'] }}
                            </option>
                        </select>
                    </template>

                    <template v-else-if="field.type === 'switch'">
                        <label class="flex cursor-pointer items-center justify-between gap-3">
                            <span class="text-sm font-medium text-[var(--maya-text-main)]">{{ field.label }}</span>
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="modelValue[field.key] ?? false"
                                @change="updateField(field.key, $event.target.checked)"
                            >
                            <span
                                class="relative inline-flex h-7 w-14 items-center rounded-full border border-transparent p-1 transition-all duration-300 ease-in-out overflow-hidden"
                                :class="[
                                    (modelValue[field.key] ?? false)
                                        ? 'bg-[var(--maya-primary)]'
                                        : 'bg-slate-600'
                                ]"
                            >
                                <span
                                    class="absolute top-1 inline-flex h-5 w-5 rounded-full bg-white shadow-md transition-all duration-300 ease-in-out will-change-transform dark:bg-[var(--maya-bg-surface)]"
                                    :style="{
                                        left: (modelValue[field.key] ?? false) ? 'calc(100% - 1.25rem)' : '0.25rem'
                                    }"
                                />
                            </span>
                        </label>
                    </template>

                    <template v-else-if="field.type === 'map'">
                        <div
                            :ref="setMapContainer"
                            class="mt-1 w-full rounded-md border border-[var(--maya-border)]"
                            style="height: 300px; z-index: 1"
                        />
                        <p class="mt-1 text-xs text-[var(--maya-text-muted)]">
                            Haz clic en el mapa para seleccionar la ubicación.
                        </p>
                        <InputError class="mt-1" :message="errors[field.key]?.[0]" />
                    </template>

                    <template v-else>
                        <TextInput
                            :model-value="modelValue[field.key]"
                            :type="field.isPassword ? 'password' : (field.type || 'text')"
                            class="mt-1 block w-full !bg-[var(--maya-bg-surface)] !text-[var(--maya-text-main)] !border-[var(--maya-border)] placeholder:!text-[var(--maya-text-muted)] placeholder:!opacity-40"
                            :placeholder="field.placeholder"
                            @update:model-value="updateField(field.key, field.valueType === 'number' ? ($event === '' ? null : Number($event)) : $event)"
                        />
                    </template>

                    <InputError class="mt-1" :message="errors[field.key]?.[0]" />
                </div>
            </div>

            <InputError class="mt-3" :message="errors.form?.[0]" />

            <div class="mt-6 flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                    @click="$emit('close')"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-md bg-[var(--maya-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--maya-primary-dark)] disabled:opacity-60"
                    :disabled="loading"
                    @click="$emit('submit')"
                >
                    {{ loading ? 'Guardando...' : submitLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
