<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useAlert } from '@/Composables/useAlert';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: undefined,
    },
    show: {
        type: Boolean,
        default: undefined,
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: '',
    },
    cancelText: {
        type: String,
        default: '',
    },
    isConfirm: {
        type: Boolean,
        default: false,
    },
    closeOnClickOutside: {
        type: Boolean,
        default: false,
    },
    closeOnEsc: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'close', 'confirm', 'cancel']);

const { alertState, confirmAction, cancelAction } = useAlert();

// Distinguish between local/controlled usage (:show / v-model) and global composable usage
const isControlled = computed(() => {
    return props.show !== undefined || props.modelValue !== undefined;
});

const isOpen = computed(() => {
    if (isControlled.value) {
        return props.modelValue !== undefined ? props.modelValue : props.show;
    }
    return alertState.isOpen;
});

const isConfirmation = computed(() => {
    if (props.isConfirm) return true;
    if (!isControlled.value) return alertState.isConfirm;
    return false;
});

const resolvedTitle = computed(() => {
    if (props.title) return props.title;
    if (!isControlled.value && alertState.title) return alertState.title;
    return import.meta.env.VITE_APP_NAME || 'MAYA';
});

const resolvedMessage = computed(() => {
    if (props.message) return props.message;
    if (!isControlled.value) return alertState.message;
    return '';
});

const resolvedConfirmText = computed(() => {
    if (props.confirmText) return props.confirmText;
    if (!isControlled.value && alertState.confirmText) return alertState.confirmText;
    return 'Aceptar';
});

const resolvedCancelText = computed(() => {
    if (props.cancelText) return props.cancelText;
    if (!isControlled.value && alertState.cancelText) return alertState.cancelText;
    return 'Cancelar';
});

const confirmButtonRef = ref(null);

const handleConfirm = () => {
    if (isControlled.value) {
        emit('confirm');
        emit('update:modelValue', false);
        emit('close');
    } else {
        confirmAction();
    }
};

const handleCancel = () => {
    if (isControlled.value) {
        emit('cancel');
        emit('update:modelValue', false);
        emit('close');
    } else {
        cancelAction();
    }
};

const handleBackdropClick = () => {
    if (props.closeOnClickOutside) {
        handleCancel();
    }
};

const handleKeydown = (event) => {
    if (!isOpen.value) return;

    if (event.key === 'Escape' && props.closeOnEsc) {
        event.preventDefault();
        handleCancel();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        handleConfirm();
    }
};

watch(
    isOpen,
    (open) => {
        if (open) {
            document.body.style.overflow = 'hidden';
            nextTick(() => {
                confirmButtonRef.value?.focus();
            });
        } else {
            document.body.style.overflow = '';
        }
    },
    { immediate: true },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[100] overflow-y-auto bg-slate-900/40 backdrop-blur-[1px] dark:bg-black/60"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="alert-dialog-title"
                aria-describedby="alert-dialog-message"
                @click="handleBackdropClick"
            >
                <div class="flex min-h-full items-start justify-center p-4 pt-16 sm:pt-24 text-center">
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 -translate-y-2 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 -translate-y-2 scale-95"
                    >
                        <div
                            v-if="isOpen"
                            class="relative w-full max-w-sm sm:max-w-md transform overflow-hidden rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-5 text-left shadow-2xl transition-all"
                            @click.stop
                        >
                            <!-- Header mimicking browser alert header -->
                            <div class="flex items-center justify-between pb-3 border-b border-[var(--maya-border)]">
                                <h3
                                    id="alert-dialog-title"
                                    class="text-xs font-semibold text-[var(--maya-text-muted)] select-none tracking-wide"
                                >
                                    {{ resolvedTitle }}
                                </h3>
                            </div>

                            <!-- Body message -->
                            <div class="py-4">
                                <p
                                    id="alert-dialog-message"
                                    class="text-sm leading-relaxed text-[var(--maya-text-main)] whitespace-pre-line break-words select-text"
                                >
                                    <slot>{{ resolvedMessage }}</slot>
                                </p>
                            </div>

                            <!-- Footer action buttons -->
                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button
                                    v-if="isConfirmation"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[var(--maya-text-main)] shadow-sm transition duration-150 ease-in-out hover:bg-[var(--maya-hover-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] active:scale-[0.98]"
                                    @click="handleCancel"
                                >
                                    {{ resolvedCancelText }}
                                </button>
                                <button
                                    ref="confirmButtonRef"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition duration-150 ease-in-out hover:bg-[var(--maya-primary-dark)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] active:scale-[0.98]"
                                    @click="handleConfirm"
                                >
                                    {{ resolvedConfirmText }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
