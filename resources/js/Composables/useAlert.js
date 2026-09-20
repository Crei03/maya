import { reactive, readonly } from 'vue';

const alertState = reactive({
    isOpen: false,
    isConfirm: false,
    title: '',
    message: '',
    confirmText: 'Aceptar',
    cancelText: 'Cancelar',
    type: 'info',
    resolve: null,
});

/**
 * Composable to trigger native-like alerts and confirms programmatically.
 *
 * Usage:
 * const { showAlert, showConfirm } = useAlert();
 * showAlert('Mensaje simple');
 *
 * const confirmed = await showConfirm('¿Estás seguro de eliminar este registro?');
 * if (!confirmed) return;
 */
export function useAlert() {
    const showAlert = (options) => {
        return new Promise((resolve) => {
            if (typeof options === 'string') {
                alertState.title = '';
                alertState.message = options;
                alertState.confirmText = 'Aceptar';
                alertState.cancelText = 'Cancelar';
                alertState.type = 'info';
            } else {
                alertState.title = options?.title || '';
                alertState.message = options?.message || '';
                alertState.confirmText = options?.confirmText || 'Aceptar';
                alertState.cancelText = options?.cancelText || 'Cancelar';
                alertState.type = options?.type || 'info';
            }
            alertState.isConfirm = false;
            alertState.resolve = resolve;
            alertState.isOpen = true;
        });
    };

    const showConfirm = (options) => {
        return new Promise((resolve) => {
            if (typeof options === 'string') {
                alertState.title = '';
                alertState.message = options;
                alertState.confirmText = 'Aceptar';
                alertState.cancelText = 'Cancelar';
                alertState.type = 'warning';
            } else {
                alertState.title = options?.title || '';
                alertState.message = options?.message || '';
                alertState.confirmText = options?.confirmText || 'Aceptar';
                alertState.cancelText = options?.cancelText || 'Cancelar';
                alertState.type = options?.type || 'warning';
            }
            alertState.isConfirm = true;
            alertState.resolve = resolve;
            alertState.isOpen = true;
        });
    };

    const confirmAction = () => {
        if (alertState.resolve) {
            alertState.resolve(true);
        }
        alertState.isOpen = false;
        alertState.resolve = null;
    };

    const cancelAction = () => {
        if (alertState.resolve) {
            alertState.resolve(false);
        }
        alertState.isOpen = false;
        alertState.resolve = null;
    };

    const closeAlert = () => {
        cancelAction();
    };

    return {
        alertState: readonly(alertState),
        showAlert,
        showConfirm,
        confirmAction,
        cancelAction,
        closeAlert,
    };
}

export default useAlert;
