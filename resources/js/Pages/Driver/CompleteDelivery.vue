<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DriverLayout from '@/Layouts/DriverLayout.vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    task: {
        type: Object,
        required: true,
    },
});

// Form state
const signedBy = ref(props.item.recipient_name || '');
const deliveryNotes = ref('');
const photos = ref([]); // Array of base64 strings
const isSubmitting = ref(false);
const photoInput = ref(null);

// Signature Canvas State
const canvasRef = ref(null);
const isDrawing = ref(false);
const hasSignature = ref(false);
let ctx = null;

// Photo Upload Logic (Min 1, Max 5)
const triggerCamera = () => {
    if (photos.value.length >= 5) return;
    if (photoInput.value) {
        photoInput.value.click();
    }
};

const handlePhotoSelected = (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    for (let i = 0; i < files.length; i++) {
        if (photos.value.length >= 5) break;

        const file = files[i];
        const reader = new FileReader();
        reader.onload = (e) => {
            photos.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
    }

    // Reset input value so same photo can be re-selected if needed
    event.target.value = '';
};

const removePhoto = (index) => {
    photos.value.splice(index, 1);
};

// Canvas Signature Logic
const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    // Get display size
    const rect = canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;

    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;

    ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.strokeStyle = '#0f172a'; // slate-900
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
};

const startDrawing = (e) => {
    isDrawing.value = true;
    hasSignature.value = true;
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
};

const stopDrawing = () => {
    if (isDrawing.value) {
        ctx.closePath();
        isDrawing.value = false;
    }
};

const getPos = (e) => {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    const clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
    const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
};

const clearSignature = () => {
    if (!canvasRef.value || !ctx) return;
    const dpr = window.devicePixelRatio || 1;
    ctx.clearRect(0, 0, canvasRef.value.width / dpr, canvasRef.value.height / dpr);
    hasSignature.value = false;
};

onMounted(() => {
    initCanvas();
    // Re-adjust canvas on orientation/resize
    window.addEventListener('resize', initCanvas);
});

// Validation
const canSubmit = computed(() => {
    return (
        photos.value.length >= 1 &&
        photos.value.length <= 5 &&
        hasSignature.value &&
        signedBy.value.trim().length > 0 &&
        !isSubmitting.value
    );
});

// Final Submission
const submitDelivery = () => {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    const canvas = canvasRef.value;
    const signatureDataUrl = canvas.toDataURL('image/png');

    router.post(
        route('driver.tasks.complete.store', props.item.id),
        {
            signed_by: signedBy.value.trim(),
            signature: signatureDataUrl,
            photos: photos.value,
            notes: deliveryNotes.value.trim(),
        },
        {
            onError: () => {
                isSubmitting.value = false;
            },
        }
    );
};
</script>

<template>
    <Head :title="`Completar Entrega - ${item.tracking_number}`" />

    <DriverLayout>
        <div class="space-y-4">
            <!-- Header Bar -->
            <div class="flex items-center justify-between gap-2">
                <Link
                    :href="route('driver.tasks', item.shipment_task_id)"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors py-1 px-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <font-awesome-icon :icon="['fas', 'chevron-left']" class="text-[10px]" />
                    <span>Volver a Paradas</span>
                </Link>

                <span class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400">
                    {{ task.title }}
                </span>
            </div>

            <!-- Stop Summary Card -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700/70 shadow-sm space-y-2.5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            Destinatario
                        </span>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                            {{ item.recipient_name }}
                        </h2>
                    </div>

                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                        {{ item.tracking_number }}
                    </span>
                </div>

                <div class="text-xs text-slate-600 dark:text-slate-300 flex items-start gap-1.5 bg-slate-50 dark:bg-slate-900/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                    <font-awesome-icon :icon="['fas', 'map-pin']" class="text-slate-400 text-xs mt-0.5 flex-shrink-0" />
                    <span>{{ item.destination_address }}</span>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <span>{{ item.package_type }}</span>
                    <span v-if="item.weight_kg">Peso: {{ item.weight_kg }} kg</span>
                </div>
            </div>

            <!-- SECTION 1: EVIDENCIA FOTOGRÁFICA (MIN 1, MAX 5) -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700/70 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <font-awesome-icon :icon="['fas', 'camera']" class="text-indigo-600 dark:text-indigo-400" />
                            Fotos de Entrega
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            Mínimo 1, máximo 5 fotografías de evidencia
                        </p>
                    </div>

                    <!-- Photo counter badge -->
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="photos.length >= 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                    >
                        {{ photos.length }} / 5 fotos
                    </span>
                </div>

                <!-- Hidden file input -->
                <input
                    ref="photoInput"
                    @change="handlePhotoSelected"
                    type="file"
                    accept="image/*"
                    capture="environment"
                    multiple
                    class="hidden"
                />

                <!-- Photo Thumbnails Grid -->
                <div class="grid grid-cols-3 gap-2.5">
                    <!-- Existing photo previews -->
                    <div
                        v-for="(photo, index) in photos"
                        :key="index"
                        class="relative aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 group shadow-xs"
                    >
                        <img :src="photo" alt="Evidencia" class="w-full h-full object-cover" />
                        <span class="absolute top-1 left-1 bg-slate-900/80 text-white text-[9px] font-bold px-1.5 py-0.2 rounded-md">
                            #{{ index + 1 }}
                        </span>
                        <button
                            @click="removePhoto(index)"
                            type="button"
                            class="absolute top-1 right-1 w-6 h-6 rounded-full bg-rose-600/90 hover:bg-rose-700 text-white flex items-center justify-center text-[10px] shadow-sm transition-transform active:scale-90"
                            title="Eliminar foto"
                        >
                            <font-awesome-icon :icon="['fas', 'trash']" />
                        </button>
                    </div>

                    <!-- Add Photo Trigger Button -->
                    <button
                        v-if="photos.length < 5"
                        @click="triggerCamera"
                        type="button"
                        class="aspect-square rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-400 bg-slate-50 dark:bg-slate-800/40 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 flex flex-col items-center justify-center gap-1 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all active:scale-[0.98]"
                    >
                        <font-awesome-icon :icon="['fas', 'camera']" class="text-base" />
                        <span class="text-[10px] font-bold">Tomar Foto</span>
                    </button>
                </div>

                <!-- Validation alert if 0 photos -->
                <div v-if="photos.length === 0" class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 text-amber-800 dark:text-amber-200 text-[11px] flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="text-amber-600" />
                    <span>Se requiere al menos 1 fotografía del paquete entregado.</span>
                </div>
            </div>

            <!-- SECTION 2: FIRMA DIGITAL EN CANVAS -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700/70 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <font-awesome-icon :icon="['fas', 'signature']" class="text-indigo-600 dark:text-indigo-400" />
                            Firma del Cliente
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            Firma en el recuadro usando la pantalla táctil
                        </p>
                    </div>

                    <button
                        @click="clearSignature"
                        type="button"
                        class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1"
                    >
                        <font-awesome-icon :icon="['fas', 'rotate-left']" class="text-[9px]" />
                        <span>Limpiar</span>
                    </button>
                </div>

                <!-- Canvas Box -->
                <div class="relative w-full h-44 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 overflow-hidden touch-none select-none">
                    <canvas
                        ref="canvasRef"
                        @pointerdown="startDrawing"
                        @pointermove="draw"
                        @pointerup="stopDrawing"
                        @pointercancel="stopDrawing"
                        @touchstart.prevent="startDrawing"
                        @touchmove.prevent="draw"
                        @touchend.prevent="stopDrawing"
                        class="w-full h-full cursor-crosshair touch-none"
                    ></canvas>

                    <!-- Placeholder text when empty -->
                    <div
                        v-if="!hasSignature"
                        class="absolute inset-0 pointer-events-none flex items-center justify-center text-slate-400 dark:text-slate-500 text-xs font-medium"
                    >
                        Dibuje la firma aquí...
                    </div>
                </div>

                <!-- Input Nombre del receptor -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Nombre completo de quien recibe:
                    </label>
                    <input
                        v-model="signedBy"
                        type="text"
                        placeholder="Nombre y apellido completo"
                        class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    />
                    <p class="text-[10px] text-slate-400 mt-1">
                        La firma digital se guardará archivada con el nombre de esta persona.
                    </p>
                </div>

                <!-- Notas adicionales opcionales -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                        Notas u observaciones de entrega (opcional):
                    </label>
                    <input
                        v-model="deliveryNotes"
                        type="text"
                        placeholder="Ej: Recibido por recepción / vecino..."
                        class="w-full p-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100"
                    />
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="pt-2">
                <button
                    @click="submitDelivery"
                    :disabled="!canSubmit"
                    type="button"
                    class="w-full py-4 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 disabled:opacity-40 disabled:pointer-events-none text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20 transition-all active:scale-[0.99]"
                >
                    <font-awesome-icon
                        v-if="isSubmitting"
                        :icon="['fas', 'spinner']"
                        class="fa-spin text-base"
                    />
                    <font-awesome-icon
                        v-else
                        :icon="['fas', 'circle-check']"
                        class="text-base"
                    />
                    <span>{{ isSubmitting ? 'Guardando evidencia y entrega...' : 'Confirmar y Guardar Entrega' }}</span>
                </button>

                <p v-if="!canSubmit" class="text-center text-[10px] text-slate-400 mt-2">
                    Para confirmar debes registrar al menos 1 foto, la firma y el nombre completo.
                </p>
            </div>
        </div>
    </DriverLayout>
</template>
