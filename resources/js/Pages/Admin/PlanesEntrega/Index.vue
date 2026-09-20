<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import Modal from '@/Components/Modal.vue';
import { useAlert } from '@/Composables/useAlert';

const { showAlert, showConfirm } = useAlert();

// --- Estado de la lista principal ---
const loading = ref(false);
const tasks = ref([]);
const pagination = ref(null);
const perPage = ref(15);
const successMessage = ref('');
const errorMessage = ref('');

const props = defineProps({
    taskStatuses: { type: Array, default: () => [] },
    itemStatuses: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
    referenceTypes: { type: Array, default: () => [] },
    packageTypes: { type: Array, default: () => [] },
});

// Helpers de normalización de estados
const isTaskPending = (task) => task?.status === 'pending' || task?.status === 'PENDIENTE' || task?.status_code === 'PENDIENTE';
const isTaskInProgress = (task) => task?.status === 'in_progress' || task?.status === 'EN_PROCESO' || task?.status_code === 'EN_PROCESO';
const isTaskCompleted = (task) => task?.status === 'completed' || task?.status === 'COMPLETADA' || task?.status_code === 'COMPLETADA';
const isTaskCancelled = (task) => task?.status === 'cancelled' || task?.status === 'CANCELADA' || task?.status_code === 'CANCELADA';

const isItemDelivered = (item) => item?.status === 'entregado' || item?.status === 'ENTREGADO' || item?.status_code === 'ENTREGADO';
const isItemReturned = (item) => item?.status === 'retornado' || item?.status === 'RETORNADO' || item?.status_code === 'RETORNADO';
const isItemPending = (item) => item?.status === 'pendiente' || item?.status === 'PENDIENTE' || item?.status_code === 'PENDIENTE';

const isPriorityHigh = (p) => {
    const val = typeof p === 'object' ? (p?.priority_code || p?.priority) : p;
    return val === 'alta' || val === 'ALTA';
};
const isPriorityMedium = (p) => {
    const val = typeof p === 'object' ? (p?.priority_code || p?.priority) : p;
    return val === 'media' || val === 'MEDIA';
};
const isPriorityLow = (p) => {
    const val = typeof p === 'object' ? (p?.priority_code || p?.priority) : p;
    return val === 'baja' || val === 'BAJA';
};

const filters = reactive({
    search: '',
    status: '',
    origin_warehouse_id: '',
});

const columns = [
    { key: 'title', label: 'Código Plan' },
    { key: 'driver_name', label: 'Conductor' },
    { key: 'vehicle_label', label: 'Vehículo' },
    { key: 'warehouse_name', label: 'Bodega Salida' },
    { key: 'start_date', label: 'Salida' },
    { key: 'progress', label: 'Progreso' },
    { key: 'status', label: 'Estado / Tiempo' },
    { key: 'actions', label: 'Acciones' },
];

// --- Catálogos para el Wizard ---
const driversList = ref([]);
const vehiclesList = ref([]);
const warehousesList = ref([]);
const clientsList = ref([]);
const availableWarehouseShipments = ref([]);
const loadingWarehouseShipments = ref(false);

// --- Estado del Wizard Modal ---
const wizardOpen = ref(false);
const currentStep = ref(1);
const savingTask = ref(false);
const wizardErrors = ref({});
const priorityWarning = ref('');

// Formulario del Wizard
const wizardForm = reactive({
    title: '',
    driver_id: '',
    vehicle_id: '',
    origin_warehouse_id: '',
    start_date: '',
    notes: '',
    items: [],
});

// Formulario de Parada rápida ("Al vuelo")
const quickStopForm = reactive({
    sender_id: '',
    recipient_name: '',
    recipient_phone: '',
    destination_address: '',
    package_type: 'caja',
    weight_lb: '',
    content_description: '',
    priority: 'media',
    reference_type: 'pedido',
    reference_number: '',
    lpn_code: '',
    pieces_count: 1,
});

// Modal de Detalle
const detailOpen = ref(false);
const detailTask = ref(null);
const loadingDetail = ref(false);
const savingReorder = ref(false);

// --- Ranking de Prioridades para Regla de Negocio ---
const PRIORITY_RANK = {
    alta: 1,
    ALTA: 1,
    media: 2,
    MEDIA: 2,
    baja: 3,
    BAJA: 3,
};

// --- Computed Helpers ---
const selectedVehicle = computed(() => {
    if (!wizardForm.vehicle_id) return null;
    return vehiclesList.value.find((v) => v.id === wizardForm.vehicle_id) || null;
});

const totalStopsWeightLb = computed(() => {
    return wizardForm.items.reduce((acc, item) => {
        const weight = Number(item.weight_lb || item.shipment?.weight_lb || 0);
        return acc + (isNaN(weight) ? 0 : weight);
    }, 0);
});

const vehicleCapacityKg = computed(() => {
    return selectedVehicle.value?.capacity_kg ? Number(selectedVehicle.value.capacity_kg) : 0;
});

const vehicleCapacityLb = computed(() => {
    return vehicleCapacityKg.value > 0 ? vehicleCapacityKg.value * 2.20462 : 0;
});

const loadCapacityPercent = computed(() => {
    if (vehicleCapacityLb.value <= 0) return 0;
    const pct = (totalStopsWeightLb.value / vehicleCapacityLb.value) * 100;
    return Math.min(Math.round(pct), 100);
});

const priorityCounts = computed(() => {
    const counts = { alta: 0, media: 0, baja: 0 };
    wizardForm.items.forEach((item) => {
        if (counts[item.priority] !== undefined) {
            counts[item.priority]++;
        }
    });
    return counts;
});

// --- API Calls ---
const fetchTasks = async (page = 1) => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const params = {
            page,
            per_page: perPage.value,
            ...filters,
        };
        const response = await window.axios.get(route('admin.shipment-tasks.list'), { params });
        if (response.data.success) {
            tasks.value = response.data.data.data;
            pagination.value = response.data.data.meta;
        }
    } catch (err) {
        errorMessage.value = 'Error al cargar los planes de entrega.';
    } finally {
        loading.value = false;
    }
};

const fetchCatalogs = async () => {
    try {
        // Conductores (messengers)
        const driversRes = await window.axios.get(route('admin.drivers.list'), { params: { per_page: 100, is_available: true } });
        if (driversRes.data.success) {
            driversList.value = driversRes.data.data.data || driversRes.data.data;
        }

        // Vehículos
        const vehiclesRes = await window.axios.get(route('admin.vehicles.list'), { params: { per_page: 100, is_active: true } });
        if (vehiclesRes.data.success) {
            vehiclesList.value = vehiclesRes.data.data.data || vehiclesRes.data.data;
        }

        // Bodegas
        const warehousesRes = await window.axios.get(route('admin.bodegas.list'), { params: { per_page: 100, is_active: true } });
        if (warehousesRes.data.success) {
            warehousesList.value = warehousesRes.data.data.data || warehousesRes.data.data;
        }

        // Clientes
        const clientsRes = await window.axios.get(route('admin.clients.list'), { params: { per_page: 200 } });
        if (clientsRes.data.success) {
            clientsList.value = clientsRes.data.data.data || clientsRes.data.data;
        }
    } catch (e) {
        console.warn('Error cargando catálogos auxiliares', e);
    }
};

const fetchNextCode = async () => {
    try {
        const res = await window.axios.get(route('admin.shipment-tasks.next-code'));
        if (res.data.success) {
            wizardForm.title = res.data.data.code;
        }
    } catch {
        wizardForm.title = 'PLE-' + new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-0001';
    }
};

const fetchAvailableWarehouseShipments = async (warehouseId) => {
    if (!warehouseId) {
        availableWarehouseShipments.value = [];
        return;
    }
    loadingWarehouseShipments.value = true;
    try {
        const res = await window.axios.get(route('admin.shipments.list'), {
            params: {
                warehouse_id: warehouseId,
                status: 'in_warehouse',
                per_page: 100,
            },
        });
        if (res.data.success) {
            availableWarehouseShipments.value = res.data.data.data || [];
        }
    } catch (e) {
        console.warn('Error al buscar paquetes de bodega', e);
    } finally {
        loadingWarehouseShipments.value = false;
    }
};

// --- Manejo del Wizard ---
const openWizard = async () => {
    successMessage.value = '';
    errorMessage.value = '';
    wizardErrors.value = {};
    priorityWarning.value = '';
    currentStep.value = 1;

    // Calcular fecha por defecto (hoy + 1 hora en formato YYYY-MM-DDTHH:mm)
    const nextHour = new Date();
    nextHour.setHours(nextHour.getHours() + 1);
    nextHour.setMinutes(0);
    const localIso = new Date(nextHour.getTime() - nextHour.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

    Object.assign(wizardForm, {
        title: '',
        driver_id: '',
        vehicle_id: '',
        origin_warehouse_id: warehousesList.value[0]?.id || '',
        start_date: localIso,
        notes: '',
        items: [],
    });

    resetQuickStopForm();
    await fetchNextCode();
    if (wizardForm.origin_warehouse_id) {
        await fetchAvailableWarehouseShipments(wizardForm.origin_warehouse_id);
    }
    wizardOpen.value = true;
};

const resetQuickStopForm = () => {
    Object.assign(quickStopForm, {
        sender_id: clientsList.value[0]?.id || '',
        recipient_name: '',
        recipient_phone: '',
        destination_address: '',
        package_type: 'caja',
        weight_lb: '',
        content_description: '',
        priority: 'media',
        reference_type: 'pedido',
        reference_number: '',
        lpn_code: '',
        pieces_count: 1,
    });
};

const closeWizard = () => {
    wizardOpen.value = false;
};

// --- Lógica de Paradas y Regla de Prioridad ---

// Función para re-indexar paradas 1, 2, 3...
const reindexStops = () => {
    wizardForm.items.forEach((item, idx) => {
        item.stop_order = idx + 1;
    });
};

// Agregar Parada "Al Vuelo"
const addQuickStop = () => {
    priorityWarning.value = '';

    if (!quickStopForm.sender_id) {
        priorityWarning.value = 'Debes seleccionar el cliente remitente.';
        return;
    }
    if (!quickStopForm.destination_address) {
        priorityWarning.value = 'La dirección de destino es requerida.';
        return;
    }
    const weight = parseFloat(quickStopForm.weight_lb);
    if (isNaN(weight) || weight <= 0) {
        priorityWarning.value = 'Ingresa un peso válido en libras.';
        return;
    }

    const senderObj = clientsList.value.find((c) => c.id === quickStopForm.sender_id);
    const clientName = senderObj ? (senderObj.full_name || `${senderObj.first_name} ${senderObj.last_name}`) : 'Cliente';

    const newStop = {
        temp_id: 'stop-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5),
        priority: quickStopForm.priority,
        stop_order: wizardForm.items.length + 1,
        weight_lb: weight,
        destination_address: quickStopForm.destination_address,
        recipient_name: quickStopForm.recipient_name || clientName,
        recipient_phone: quickStopForm.recipient_phone || senderObj?.phone || '',
        package_type: quickStopForm.package_type,
        sender_name: clientName,
        reference_type: quickStopForm.reference_type || '',
        reference_number: quickStopForm.reference_number || '',
        lpn_code: quickStopForm.lpn_code || '',
        pieces_count: parseInt(quickStopForm.pieces_count) || 1,
        new_shipment: {
            sender_id: quickStopForm.sender_id || null,
            recipient_name: quickStopForm.recipient_name || clientName,
            recipient_phone: quickStopForm.recipient_phone || senderObj?.phone || null,
            destination_address: quickStopForm.destination_address,
            package_type: quickStopForm.package_type,
            weight_lb: weight,
            content_description: quickStopForm.content_description || null,
            reference_type: quickStopForm.reference_type || null,
            reference_number: quickStopForm.reference_number || null,
            lpn_code: quickStopForm.lpn_code || null,
            pieces_count: parseInt(quickStopForm.pieces_count) || 1,
        },
    };

    // Insertar la parada en la posición correcta según su prioridad
    insertStopByPriority(newStop);
    resetQuickStopForm();
};

// Incorporar paquete existente de bodega
const importWarehouseShipment = (shipment) => {
    priorityWarning.value = '';

    // Verificar si ya está agregado
    if (wizardForm.items.some((i) => i.shipment_id === shipment.id)) {
        priorityWarning.value = `El paquete ${shipment.tracking_number} ya está agregado a esta ruta.`;
        return;
    }

    const newStop = {
        temp_id: 'stop-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5),
        shipment_id: shipment.id,
        priority: 'media',
        stop_order: wizardForm.items.length + 1,
        weight_lb: shipment.weight_lb || 0,
        destination_address: shipment.destination_address,
        recipient_name: shipment.recipient_name || shipment.client_name || 'Destinatario',
        recipient_phone: shipment.recipient_phone || shipment.client_phone || '',
        package_type: shipment.package_type || 'paquete',
        sender_name: shipment.sender?.full_name || shipment.recipient_name || 'Cliente bodega',
        tracking_number: shipment.tracking_number,
        reference_type: shipment.reference_type || '',
        reference_number: shipment.reference_number || '',
        lpn_code: shipment.lpn_code || '',
        pieces_count: shipment.pieces_count || 1,
    };

    insertStopByPriority(newStop);
};

// Inserta la parada al final de su respectivo bloque de prioridad
const insertStopByPriority = (newStop) => {
    const targetRank = PRIORITY_RANK[newStop.priority];

    // Encontrar el último índice con rango <= targetRank
    let insertIndex = wizardForm.items.length;
    for (let i = 0; i < wizardForm.items.length; i++) {
        const itemRank = PRIORITY_RANK[wizardForm.items[i].priority];
        if (itemRank > targetRank) {
            insertIndex = i;
            break;
        }
    }

    wizardForm.items.splice(insertIndex, 0, newStop);
    reindexStops();
};

// Eliminar parada
const removeStop = (index) => {
    wizardForm.items.splice(index, 1);
    reindexStops();
};

// Mover parada hacia arriba
const moveStopUp = (index) => {
    priorityWarning.value = '';
    if (index === 0) return;

    const currentItem = wizardForm.items[index];
    const prevItem = wizardForm.items[index - 1];

    const currentRank = PRIORITY_RANK[currentItem.priority];
    const prevRank = PRIORITY_RANK[prevItem.priority];

    // Regla de negocio: No permitir que una parada de prioridad baja supere a una de prioridad alta
    if (currentRank > prevRank) {
        priorityWarning.value = `Violación de prioridad: No puedes mover una entrega de prioridad ${currentItem.priority.toUpperCase()} por encima de una de prioridad ${prevItem.priority.toUpperCase()}.`;
        return;
    }

    // Intercambiar
    wizardForm.items[index] = prevItem;
    wizardForm.items[index - 1] = currentItem;
    reindexStops();
};

// Mover parada hacia abajo
const moveStopDown = (index) => {
    priorityWarning.value = '';
    if (index >= wizardForm.items.length - 1) return;

    const currentItem = wizardForm.items[index];
    const nextItem = wizardForm.items[index + 1];

    const currentRank = PRIORITY_RANK[currentItem.priority];
    const nextRank = PRIORITY_RANK[nextItem.priority];

    // Regla de negocio: No permitir que una parada de prioridad alta caiga después de una de prioridad baja
    if (currentRank < nextRank) {
        priorityWarning.value = `Violación de prioridad: Una entrega de prioridad ${currentItem.priority.toUpperCase()} no puede colocarse después de una de prioridad ${nextItem.priority.toUpperCase()}.`;
        return;
    }

    // Intercambiar
    wizardForm.items[index] = nextItem;
    wizardForm.items[index + 1] = currentItem;
    reindexStops();
};

// Cambiar prioridad de una parada en la lista
const changeStopPriority = (item, newPriority) => {
    if (item.priority === newPriority) return;
    item.priority = newPriority;

    // Remover y reinsertar en el bloque adecuado para mantener consistencia
    const itemIndex = wizardForm.items.indexOf(item);
    if (itemIndex > -1) {
        wizardForm.items.splice(itemIndex, 1);
        insertStopByPriority(item);
    }
};

// Validar y avanzar pasos
const nextStep = () => {
    priorityWarning.value = '';
    wizardErrors.value = {};

    if (currentStep.value === 1) {
        if (!wizardForm.driver_id) {
            wizardErrors.value.driver_id = 'Debes seleccionar un conductor.';
            return;
        }
        if (!wizardForm.origin_warehouse_id) {
            wizardErrors.value.origin_warehouse_id = 'Debes seleccionar una bodega de salida.';
            return;
        }
        if (!wizardForm.start_date) {
            wizardErrors.value.start_date = 'Debes indicar la fecha y hora de salida.';
            return;
        }
        currentStep.value = 2;
    } else if (currentStep.value === 2) {
        if (wizardForm.items.length === 0) {
            priorityWarning.value = 'Debes agregar al menos una parada al plan de entrega.';
            return;
        }
        currentStep.value = 3;
    }
};

const prevStep = () => {
    priorityWarning.value = '';
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

// Guardar Plan de Entrega (Paso 3)
const submitPlan = async () => {
    savingTask.value = true;
    wizardErrors.value = {};
    priorityWarning.value = '';

    const payload = {
        title: wizardForm.title,
        driver_id: Number(wizardForm.driver_id),
        vehicle_id: wizardForm.vehicle_id || null,
        origin_warehouse_id: wizardForm.origin_warehouse_id,
        start_date: wizardForm.start_date,
        notes: wizardForm.notes,
        items: wizardForm.items.map((item) => ({
            priority: item.priority,
            stop_order: item.stop_order,
            shipment_id: item.shipment_id || null,
            new_shipment: item.new_shipment || null,
        })),
    };

    try {
        const response = await window.axios.post(route('admin.shipment-tasks.store'), payload);
        if (response.data.success) {
            successMessage.value = response.data.message || 'Plan de entrega creado exitosamente.';
            closeWizard();
            await fetchTasks(1);
        }
    } catch (err) {
        if (err?.response?.status === 422) {
            const errs = err.response.data.errors || {};
            wizardErrors.value = errs;
            if (errs.items) {
                priorityWarning.value = Array.isArray(errs.items) ? errs.items[0] : errs.items;
                currentStep.value = 2; // Regresar al paso 2 si hay error de paradas
            }
        } else {
            errorMessage.value = 'Ocurrió un error inesperado al guardar el plan de entrega.';
        }
    } finally {
        savingTask.value = false;
    }
};

// --- Modal de Detalle y Reordenamiento ---
const openDetailModal = async (task) => {
    detailTask.value = null;
    detailOpen.value = true;
    loadingDetail.value = true;
    try {
        const res = await window.axios.get(route('admin.shipment-tasks.show', { id: task.id }));
        if (res.data.success) {
            detailTask.value = res.data.data;
        }
    } catch {
        errorMessage.value = 'No se pudo cargar el detalle del plan.';
    } finally {
        loadingDetail.value = false;
    }
};

const closeDetailModal = () => {
    detailOpen.value = false;
};

// Reordenar en vista de detalle
const moveDetailStopUp = (index) => {
    if (!detailTask.value || index === 0) return;
    const items = detailTask.value.items;
    const currentItem = items[index];
    const prevItem = items[index - 1];

    if (PRIORITY_RANK[currentItem.priority] > PRIORITY_RANK[prevItem.priority]) {
        showAlert('Violación de prioridad: No puedes mover una entrega de prioridad menor antes de una mayor.');
        return;
    }

    items[index] = prevItem;
    items[index - 1] = currentItem;
    items.forEach((it, i) => { it.stop_order = i + 1; });
};

const moveDetailStopDown = (index) => {
    if (!detailTask.value || index >= detailTask.value.items.length - 1) return;
    const items = detailTask.value.items;
    const currentItem = items[index];
    const nextItem = items[index + 1];

    if (PRIORITY_RANK[currentItem.priority] < PRIORITY_RANK[nextItem.priority]) {
        showAlert('Violación de prioridad: Una entrega de mayor prioridad no puede quedar después de una menor.');
        return;
    }

    items[index] = nextItem;
    items[index + 1] = currentItem;
    items.forEach((it, i) => { it.stop_order = i + 1; });
};

const saveDetailReorder = async () => {
    if (!detailTask.value) return;
    savingReorder.value = true;
    try {
        const payload = {
            items: detailTask.value.items.map((it) => ({
                id: it.id,
                stop_order: it.stop_order,
                priority: it.priority,
            })),
        };
        const res = await window.axios.patch(route('admin.shipment-tasks.reorder', { id: detailTask.value.id }), payload);
        if (res.data.success) {
            detailTask.value = res.data.data;
            successMessage.value = 'Orden de paradas actualizado exitosamente.';
            await fetchTasks(pagination.value?.current_page || 1);
        }
    } catch {
        showAlert('Error al guardar el nuevo orden.');
    } finally {
        savingReorder.value = false;
    }
};

// --- Cronómetro en Tiempo Real ---
const currentNow = ref(Date.now());
let timerInterval = null;

const formatElapsedTime = (startDateStr) => {
    if (!startDateStr) return '00:00:00';
    const start = new Date(startDateStr).getTime();
    if (isNaN(start)) return '00:00:00';
    const elapsedSec = Math.max(0, Math.floor((currentNow.value - start) / 1000));
    const hours = String(Math.floor(elapsedSec / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((elapsedSec % 3600) / 60)).padStart(2, '0');
    const seconds = String(elapsedSec % 60).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
};

// --- Acciones del Ciclo de Vida de Tareas ---
const confirmStartTask = async (task) => {
    const confirmed = await showConfirm(`¿Deseas iniciar el plan de entrega "${task.title}"?\nLos paquetes pasarán a estado "En tránsito".`);
    if (!confirmed) {
        return;
    }
    loading.value = true;
    errorMessage.value = '';
    try {
        const res = await window.axios.post(route('admin.shipment-tasks.start', { id: task.id }));
        if (res.data.success) {
            successMessage.value = `¡Plan ${task.title} iniciado exitosamente!`;
            await fetchTasks(pagination.value?.current_page || 1);
            if (detailOpen.value && detailTask.value?.id === task.id) {
                detailTask.value = res.data.data;
            }
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Error al iniciar el plan de entrega.';
    } finally {
        loading.value = false;
    }
};

const confirmCompleteTask = async (task) => {
    const confirmed = await showConfirm(`¿Deseas finalizar la ruta del plan "${task.title}"?\nSe registrará la hora de término y los paquetes no entregados retornarán al inventario de bodega.`);
    if (!confirmed) {
        return;
    }
    loading.value = true;
    errorMessage.value = '';
    try {
        const res = await window.axios.post(route('admin.shipment-tasks.complete', { id: task.id }));
        if (res.data.success) {
            successMessage.value = `¡Plan ${task.title} finalizado correctamente!`;
            await fetchTasks(pagination.value?.current_page || 1);
            if (detailOpen.value && detailTask.value?.id === task.id) {
                detailTask.value = res.data.data;
            }
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Error al completar el plan de entrega.';
    } finally {
        loading.value = false;
    }
};

// --- Modal de Cancelación ---
const cancelModalOpen = ref(false);
const taskToCancel = ref(null);
const cancelReason = ref('');
const cancellingTask = ref(false);

const openCancelTaskModal = (task) => {
    taskToCancel.value = task;
    cancelReason.value = '';
    cancelModalOpen.value = true;
};

const submitCancelTask = async () => {
    if (!taskToCancel.value) return;
    cancellingTask.value = true;
    try {
        const res = await window.axios.post(route('admin.shipment-tasks.cancel', { id: taskToCancel.value.id }), {
            reason: cancelReason.value || 'Cancelado por el gestor',
        });
        if (res.data.success) {
            successMessage.value = `Plan ${taskToCancel.value.title} cancelado. Paquetes retornados a bodega.`;
            cancelModalOpen.value = false;
            await fetchTasks(pagination.value?.current_page || 1);
            if (detailOpen.value && detailTask.value?.id === taskToCancel.value.id) {
                detailTask.value = res.data.data;
            }
        }
    } catch (err) {
        showAlert(err.response?.data?.message || 'Error al cancelar el plan.');
    } finally {
        cancellingTask.value = false;
    }
};

// --- Gestión de Paradas en Detalle (Entrega / Retorno / Desasignación) ---
const markStopDelivered = async (item) => {
    if (!detailTask.value) return;
    try {
        const res = await window.axios.patch(route('admin.shipment-tasks.update-item', {
            id: detailTask.value.id,
            itemId: item.id,
        }), {
            status: 'entregado',
        });
        if (res.data.success) {
            item.status = 'entregado';
            item.delivered_at = new Date().toISOString().slice(0, 16).replace('T', ' ');
            const showRes = await window.axios.get(route('admin.shipment-tasks.show', { id: detailTask.value.id }));
            if (showRes.data.success) {
                detailTask.value = showRes.data.data;
            }
            await fetchTasks(pagination.value?.current_page || 1);
        }
    } catch (err) {
        showAlert(err.response?.data?.message || 'Error al marcar parada como entregada.');
    }
};

const returnStopModalOpen = ref(false);
const stopToReturn = ref(null);
const selectedReturnReason = ref('Destinatario ausente');
const customReturnReason = ref('');
const returningStop = ref(false);

const openReturnStopModal = (item) => {
    stopToReturn.value = item;
    selectedReturnReason.value = 'Destinatario ausente';
    customReturnReason.value = '';
    returnStopModalOpen.value = true;
};

const submitReturnStop = async () => {
    if (!detailTask.value || !stopToReturn.value) return;
    returningStop.value = true;
    const finalReason = selectedReturnReason.value === 'Otro' ? customReturnReason.value : selectedReturnReason.value;
    try {
        const res = await window.axios.patch(route('admin.shipment-tasks.update-item', {
            id: detailTask.value.id,
            itemId: stopToReturn.value.id,
        }), {
            status: 'retornado',
            return_reason: finalReason || 'No entregado',
        });
        if (res.data.success) {
            stopToReturn.value.status = 'retornado';
            stopToReturn.value.return_reason = finalReason;
            returnStopModalOpen.value = false;
            const showRes = await window.axios.get(route('admin.shipment-tasks.show', { id: detailTask.value.id }));
            if (showRes.data.success) {
                detailTask.value = showRes.data.data;
            }
            await fetchTasks(pagination.value?.current_page || 1);
        }
    } catch (err) {
        showAlert(err.response?.data?.message || 'Error al registrar retorno de la parada.');
    } finally {
        returningStop.value = false;
    }
};

const unassignStopFromTask = async (item) => {
    if (!detailTask.value) return;
    const confirmed = await showConfirm(`¿Remover el paquete ${item.shipment?.tracking_number || ''} de este plan?\nRegresará al inventario disponible en bodega.`);
    if (!confirmed) {
        return;
    }
    try {
        const res = await window.axios.post(route('admin.shipment-tasks.unassign', { id: detailTask.value.id }), {
            shipment_id: item.shipment?.id,
        });
        if (res.data.success) {
            detailTask.value = res.data.data;
            successMessage.value = 'Paquete desasignado exitosamente.';
            await fetchTasks(pagination.value?.current_page || 1);
        }
    } catch (err) {
        showAlert(err.response?.data?.message || 'Error al desasignar el paquete.');
    }
};

onMounted(async () => {
    timerInterval = setInterval(() => {
        currentNow.value = Date.now();
    }, 1000);
    await fetchCatalogs();
    await fetchTasks(1);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <Head title="Planes de Entrega" />

    <AdminLayout title="Planes de Entrega">
        <div class="space-y-6">
            <!-- Header con resumen y botón de nuevo plan -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)]">
                                <font-awesome-icon :icon="['fas', 'route']" class="text-lg" />
                            </span>
                            <h1 class="text-xl font-bold text-[var(--maya-text-main)]">Planes de Entrega y Despacho</h1>
                        </div>
                        <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                            Planificación de rutas con asignación de conductor, vehículo y secuencia de entregas por prioridad.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <RefreshButton :loading="loading" @refresh="fetchTasks(1)" />
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--maya-primary-dark)]"
                            @click="openWizard"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            Nuevo Plan de Entrega
                        </button>
                    </div>
                </div>

                <!-- Mensajes de feedback -->
                <div v-if="successMessage" class="mt-4 rounded-xl border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] p-3 text-sm text-[var(--maya-success-dark)]">
                    {{ successMessage }}
                </div>
                <div v-if="errorMessage" class="mt-4 rounded-xl border border-[var(--maya-danger)] bg-[var(--maya-danger-alpha)] p-3 text-sm text-[var(--maya-danger)]">
                    {{ errorMessage }}
                </div>

                <!-- Filtros rápidos -->
                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-[var(--maya-border)] pt-4">
                    <div class="min-w-[220px] flex-1">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Buscar por código (PLE-...) o conductor"
                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-2 text-sm text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:outline-none"
                            @keyup.enter="fetchTasks(1)"
                        />
                    </div>

                    <div class="w-48">
                        <select
                            v-model="filters.status"
                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-2 text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                            @change="fetchTasks(1)"
                        >
                            <option value="">Todos los estados</option>
                            <template v-if="taskStatuses?.length">
                                <option v-for="st in taskStatuses" :key="st.id" :value="st.codigo">
                                    {{ st.valor }}
                                </option>
                            </template>
                            <template v-else>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="EN_PROCESO">En curso</option>
                                <option value="COMPLETADA">Completado</option>
                                <option value="CANCELADA">Cancelado</option>
                            </template>
                        </select>
                    </div>

                    <div class="w-52">
                        <select
                            v-model="filters.origin_warehouse_id"
                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-3 py-2 text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                            @change="fetchTasks(1)"
                        >
                            <option value="">Todas las bodegas</option>
                            <option v-for="wh in warehousesList" :key="wh.id" :value="wh.id">
                                {{ wh.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Tabla de Planes de Entrega -->
            <section class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm">
                <DataTable
                    :columns="columns"
                    :rows="tasks"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    empty-text="No hay planes de entrega registrados todavía."
                    @change-page="fetchTasks"
                >
                    <template #cell-title="{ row }">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-[var(--maya-hover-surface)] px-2.5 py-1 font-mono text-xs font-bold text-black dark:text-white">
                            <font-awesome-icon :icon="['fas', 'route']" class="text-[10px] text-[var(--maya-text-muted)]" />
                            {{ row.title }}
                        </span>
                    </template>

                    <template #cell-driver_name="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium text-[var(--maya-text-main)]">{{ row.driver_name }}</span>
                            <span v-if="row.driver_email" class="text-xs text-[var(--maya-text-muted)]">{{ row.driver_email }}</span>
                        </div>
                    </template>

                    <template #cell-vehicle_label="{ row }">
                        <span class="text-xs text-[var(--maya-text-main)]">{{ row.vehicle_label }}</span>
                    </template>

                    <template #cell-warehouse_name="{ row }">
                        <span class="text-xs text-[var(--maya-text-main)]">{{ row.warehouse_name }}</span>
                    </template>

                    <template #cell-progress="{ row }">
                        <div class="flex flex-col gap-1 min-w-[130px]">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-medium text-[var(--maya-text-main)]">
                                    {{ row.delivered_count || 0 }} / {{ row.total_items }} ent.
                                </span>
                                <span class="font-mono text-[10px] text-[var(--maya-text-muted)]">{{ row.progress_percent || 0 }}%</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-[var(--maya-hover-surface)] border border-[var(--maya-border)]">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="isTaskCompleted(row) ? 'bg-green-500' : 'bg-[var(--maya-primary)]'"
                                    :style="{ width: `${row.progress_percent || 0}%` }"
                                />
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-[var(--maya-text-muted)]">
                                <span v-if="row.priority_counts?.alta" class="inline-block h-2 w-2 rounded-full bg-red-500" title="Altas" />
                                <span v-if="row.priority_counts?.media" class="inline-block h-2 w-2 rounded-full bg-amber-500" title="Medias" />
                                <span v-if="row.priority_counts?.baja" class="inline-block h-2 w-2 rounded-full bg-gray-400" title="Bajas" />
                                <span class="ml-auto font-mono text-[10px]">{{ row.total_weight_lb }} lbs</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <div class="flex flex-col gap-1 items-start">
                            <span
                                v-if="isTaskPending(row)"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300"
                            >
                                <font-awesome-icon :icon="['fas', 'clock']" class="text-[10px]" />
                                Pendiente
                            </span>
                            <span
                                v-else-if="isTaskInProgress(row)"
                                class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-500 opacity-75" />
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-600" />
                                </span>
                                En curso
                            </span>
                            <span
                                v-else-if="isTaskCompleted(row)"
                                class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300"
                            >
                                <font-awesome-icon :icon="['fas', 'check']" class="text-[10px]" />
                                Completado
                            </span>
                            <span
                                v-else-if="isTaskCancelled(row)"
                                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300"
                            >
                                <font-awesome-icon :icon="['fas', 'xmark']" class="text-[10px]" />
                                Cancelado
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="row.status_metadata?.badge || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                            >
                                {{ row.status_label || row.status }}
                            </span>

                            <!-- Cronómetro o Duración -->
                            <div v-if="isTaskInProgress(row) && (row.start_date_raw || row.start_date)" class="flex items-center gap-1 font-mono text-[11px] font-bold text-blue-600 dark:text-blue-400">
                                <font-awesome-icon :icon="['fas', 'clock']" class="text-[10px]" />
                                <span>{{ formatElapsedTime(row.start_date_raw || row.start_date) }}</span>
                            </div>
                            <div v-else-if="isTaskCompleted(row) && row.total_hours !== null" class="font-mono text-[10px] text-[var(--maya-text-muted)]">
                                Duración: {{ row.total_hours }}h
                            </div>
                        </div>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-1.5">
                            <button
                                v-if="isTaskPending(row)"
                                type="button"
                                title="Iniciar Ruta"
                                class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
                                @click="confirmStartTask(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'truck']" />
                                Iniciar
                            </button>

                            <button
                                v-else-if="isTaskInProgress(row)"
                                type="button"
                                title="Finalizar Ruta"
                                class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-green-700"
                                @click="confirmCompleteTask(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'check']" />
                                Finalizar
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-2.5 py-1 text-xs font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                                title="Ver detalle de paradas"
                                @click="openDetailModal(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'eye']" />
                                Paradas
                            </button>

                            <button
                                v-if="!isTaskCompleted(row) && !isTaskCancelled(row)"
                                type="button"
                                title="Cancelar Plan de Entrega"
                                class="inline-flex items-center justify-center rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-900/50 dark:hover:bg-red-900/20"
                                @click="openCancelTaskModal(row)"
                            >
                                <font-awesome-icon :icon="['fas', 'xmark']" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </section>
        </div>

        <!-- ==================================================================== -->
        <!-- WIZARD MODAL: CREACIÓN DE PLAN DE ENTREGA EN 3 PASOS                 -->
        <!-- ==================================================================== -->
        <Modal :show="wizardOpen" max-width="4xl" @close="closeWizard">
            <div class="p-6">
                <!-- Header del Wizard -->
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-4">
                    <div>
                        <span class="font-mono text-xs font-bold uppercase tracking-wider text-[var(--maya-primary)]">
                            Planificador de Rutas
                        </span>
                        <h2 class="text-lg font-bold text-[var(--maya-text-main)]">
                            Nuevo Plan de Entrega: <span class="font-mono font-bold text-black dark:text-white">{{ wizardForm.title }}</span>
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]"
                        @click="closeWizard"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" class="text-lg" />
                    </button>
                </div>

                <!-- Pasos (Step Indicator) -->
                <div class="my-5 grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl p-3 text-left transition"
                        :class="currentStep === 1 ? 'bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] ring-2 ring-[var(--maya-primary)]' : 'bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)]'"
                        @click="currentStep = 1"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--maya-primary)] text-xs font-bold text-white">1</span>
                        <div>
                            <p class="text-xs font-bold">Paso 1</p>
                            <p class="text-xs">Configurar Ruta</p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl p-3 text-left transition"
                        :class="currentStep === 2 ? 'bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] ring-2 ring-[var(--maya-primary)]' : 'bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)]'"
                        :disabled="!wizardForm.driver_id || !wizardForm.origin_warehouse_id"
                        @click="nextStep"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold" :class="currentStep === 2 ? 'bg-[var(--maya-primary)] text-white' : 'bg-gray-300 text-gray-700'">2</span>
                        <div>
                            <p class="text-xs font-bold">Paso 2</p>
                            <p class="text-xs">Cargar Paradas ({{ wizardForm.items.length }})</p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl p-3 text-left transition"
                        :class="currentStep === 3 ? 'bg-[var(--maya-primary-alpha)] text-[var(--maya-primary)] ring-2 ring-[var(--maya-primary)]' : 'bg-[var(--maya-hover-surface)] text-[var(--maya-text-muted)]'"
                        :disabled="wizardForm.items.length === 0"
                        @click="currentStep = 3"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold" :class="currentStep === 3 ? 'bg-[var(--maya-primary)] text-white' : 'bg-gray-300 text-gray-700'">3</span>
                        <div>
                            <p class="text-xs font-bold">Paso 3</p>
                            <p class="text-xs">Resumen y Despacho</p>
                        </div>
                    </button>
                </div>

                <!-- Alertas del Wizard -->
                <div v-if="priorityWarning" class="mb-4 rounded-xl border border-red-300 bg-red-50 p-3 text-xs font-medium text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mr-1.5" />
                    {{ priorityWarning }}
                </div>

                <!-- ================================================================ -->
                <!-- PASO 1: CONFIGURAR RUTA                                          -->
                <!-- ================================================================ -->
                <div v-if="currentStep === 1" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Código Secuencial
                            </label>
                            <div class="mt-1 flex items-center gap-2">
                                <input
                                    v-model="wizardForm.title"
                                    type="text"
                                    class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] px-3 py-2 font-mono text-sm font-bold text-black dark:text-white focus:outline-none"
                                    readonly
                                />
                                <button
                                    type="button"
                                    class="rounded-xl border border-[var(--maya-border)] px-3 py-2 text-xs hover:bg-[var(--maya-hover-surface)]"
                                    title="Regenerar código"
                                    @click="fetchNextCode"
                                >
                                    <font-awesome-icon :icon="['fas', 'rotate-left']" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Bodega de Origen / Salida *
                            </label>
                            <select
                                v-model="wizardForm.origin_warehouse_id"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                                @change="fetchAvailableWarehouseShipments(wizardForm.origin_warehouse_id)"
                            >
                                <option value="">Selecciona una bodega</option>
                                <option v-for="wh in warehousesList" :key="wh.id" :value="wh.id">
                                    {{ wh.name }} ({{ wh.code }})
                                </option>
                            </select>
                            <p v-if="wizardErrors.origin_warehouse_id" class="mt-1 text-xs text-red-500">{{ wizardErrors.origin_warehouse_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Conductor Asignado *
                            </label>
                            <select
                                v-model="wizardForm.driver_id"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                            >
                                <option value="">Selecciona un conductor</option>
                                <option v-for="d in driversList" :key="d.id" :value="d.id">
                                    {{ d.name }} {{ d.driverProfile?.phone ? '(' + d.driverProfile.phone + ')' : '' }}
                                </option>
                            </select>
                            <p v-if="wizardErrors.driver_id" class="mt-1 text-xs text-red-500">{{ wizardErrors.driver_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Vehículo de Flota
                            </label>
                            <select
                                v-model="wizardForm.vehicle_id"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                            >
                                <option value="">Sin vehículo asignado</option>
                                <option v-for="v in vehiclesList" :key="v.id" :value="v.id">
                                    {{ v.brand }} {{ v.model }} - Placa: {{ v.license_plate }} (Cap: {{ v.capacity_kg || 'N/A' }} kg)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Fecha y Hora de Salida Programada *
                            </label>
                            <input
                                v-model="wizardForm.start_date"
                                type="datetime-local"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm text-[var(--maya-text-main)] focus:border-[var(--maya-primary)] focus:outline-none"
                            />
                            <p v-if="wizardErrors.start_date" class="mt-1 text-xs text-red-500">{{ wizardErrors.start_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--maya-text-main)]">
                                Instrucciones / Notas de Ruta
                            </label>
                            <input
                                v-model="wizardForm.notes"
                                type="text"
                                placeholder="Ej: Entregar primero en zona bancaria antes de las 12pm"
                                class="mt-1 w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-sm text-[var(--maya-text-main)] placeholder-[var(--maya-text-muted)] focus:border-[var(--maya-primary)] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--maya-primary-dark)]"
                            @click="nextStep"
                        >
                            Siguiente: Paquetes y Paradas
                            <font-awesome-icon :icon="['fas', 'arrow-right']" />
                        </button>
                    </div>
                </div>

                <!-- ================================================================ -->
                <!-- PASO 2: CARGAR Y ORDENAR PARADAS                                -->
                <!-- ================================================================ -->
                <div v-if="currentStep === 2" class="space-y-6">
                    <!-- Barra de carga del vehículo vs peso acumulado -->
                    <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3">
                        <div class="flex flex-wrap items-center justify-between text-xs font-semibold text-[var(--maya-text-main)]">
                            <span>
                                <font-awesome-icon :icon="['fas', 'truck']" class="mr-1 text-[var(--maya-primary)]" />
                                Capacidad del Transporte:
                                <span v-if="vehicleCapacityKg > 0">{{ vehicleCapacityKg }} kg (~{{ Math.round(vehicleCapacityLb) }} lbs)</span>
                                <span v-else class="text-[var(--maya-text-muted)]">No especificada</span>
                            </span>
                            <span>
                                Carga Actual: <strong class="font-mono text-sm text-[var(--maya-primary)]">{{ totalStopsWeightLb.toFixed(1) }} lbs</strong>
                                <span v-if="vehicleCapacityLb > 0"> ({{ loadCapacityPercent }}%)</span>
                            </span>
                        </div>
                        <div v-if="vehicleCapacityLb > 0" class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full transition-all duration-300"
                                :class="loadCapacityPercent > 90 ? 'bg-red-500' : loadCapacityPercent > 75 ? 'bg-amber-500' : 'bg-[var(--maya-primary)]'"
                                :style="{ width: `${loadCapacityPercent}%` }"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <!-- Formulario de ingreso rápido de paradas (Izquierda) -->
                        <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm lg:col-span-5">
                            <h3 class="flex items-center gap-1.5 text-sm font-bold text-[var(--maya-text-main)]">
                                <font-awesome-icon :icon="['fas', 'plus']" class="text-xs text-[var(--maya-primary)]" />
                                Agregar Parada al Vuelo
                            </h3>
                            <p class="mt-0.5 text-xs text-[var(--maya-text-muted)]">
                                Llena los datos y pulsa Enter o Agregar para sumar la parada.
                            </p>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Cliente Remitente *</label>
                                    <select
                                        v-model="quickStopForm.sender_id"
                                        class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                    >
                                        <option value="">Selecciona el remitente</option>
                                        <option v-for="c in clientsList" :key="c.id" :value="c.id">
                                            {{ c.full_name || `${c.first_name} ${c.last_name}` }} ({{ c.phone || 'Sin tel' }})
                                        </option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Destinatario</label>
                                        <input
                                            v-model="quickStopForm.recipient_name"
                                            type="text"
                                            placeholder="Nombre receptor"
                                            class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Teléfono Destino</label>
                                        <input
                                            v-model="quickStopForm.recipient_phone"
                                            type="text"
                                            placeholder="6000-0000"
                                            class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">N° Pedido / Doc</label>
                                        <input
                                            v-model="quickStopForm.reference_number"
                                            type="text"
                                            placeholder="Ej: PED-1002"
                                            class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs font-mono text-[var(--maya-text-main)] focus:outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">LPN / Bultos</label>
                                        <div class="mt-1 flex gap-1">
                                            <input
                                                v-model="quickStopForm.lpn_code"
                                                type="text"
                                                placeholder="LPN-..."
                                                class="w-2/3 rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2 py-1.5 text-xs font-mono text-[var(--maya-text-main)] focus:outline-none"
                                            />
                                            <input
                                                v-model="quickStopForm.pieces_count"
                                                type="number"
                                                min="1"
                                                class="w-1/3 rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-1 py-1.5 text-center text-xs font-mono font-bold text-[var(--maya-text-main)] focus:outline-none"
                                                title="Cantidad de bultos"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Dirección de Entrega *</label>
                                    <input
                                        v-model="quickStopForm.destination_address"
                                        type="text"
                                        placeholder="Calle, edificio, casa, corregimiento"
                                        class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Tipo</label>
                                        <select
                                            v-model="quickStopForm.package_type"
                                            class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs text-[var(--maya-text-main)] focus:outline-none"
                                        >
                                            <template v-if="props.packageTypes?.length">
                                                <option v-for="pkg in props.packageTypes" :key="pkg.id" :value="pkg.codigo">
                                                    {{ pkg.valor }}
                                                </option>
                                            </template>
                                            <template v-else>
                                                <option value="CAJA">Caja</option>
                                                <option value="SOBRE">Sobre</option>
                                                <option value="PAQUETE">Paquete</option>
                                                <option value="PALET">Palet</option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Peso (lbs) *</label>
                                        <input
                                            v-model="quickStopForm.weight_lb"
                                            type="number"
                                            step="0.1"
                                            placeholder="Ej: 5.5"
                                            class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2.5 py-1.5 text-xs font-mono text-[var(--maya-text-main)] focus:outline-none"
                                            @keyup.enter="addQuickStop"
                                        />
                                    </div>
                                </div>

                                <!-- Selector de Prioridad (Regla de negocio) -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">Prioridad de Entrega</label>
                                    <div class="mt-1 grid grid-cols-3 gap-1.5">
                                        <button
                                            type="button"
                                            class="rounded-lg py-1.5 text-center text-xs font-bold transition"
                                            :class="quickStopForm.priority === 'alta' ? 'bg-red-500 text-white ring-2 ring-red-300' : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300'"
                                            @click="quickStopForm.priority = 'alta'"
                                        >
                                            🔴 ALTA
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg py-1.5 text-center text-xs font-bold transition"
                                            :class="quickStopForm.priority === 'media' ? 'bg-amber-500 text-white ring-2 ring-amber-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'"
                                            @click="quickStopForm.priority = 'media'"
                                        >
                                            🟡 MEDIA
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg py-1.5 text-center text-xs font-bold transition"
                                            :class="quickStopForm.priority === 'baja' ? 'bg-gray-600 text-white ring-2 ring-gray-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                                            @click="quickStopForm.priority = 'baja'"
                                        >
                                            ⚪ BAJA
                                        </button>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="mt-2 w-full rounded-xl bg-[var(--maya-primary)] py-2 text-xs font-bold text-white shadow-sm hover:bg-[var(--maya-primary-dark)]"
                                    @click="addQuickStop"
                                >
                                    + Agregar Parada a la Ruta (Enter)
                                </button>
                            </div>

                            <!-- Opcional: Paquetes en bodega para incorporar -->
                            <div v-if="availableWarehouseShipments.length > 0" class="mt-4 border-t border-[var(--maya-border)] pt-3">
                                <label class="block text-[11px] font-semibold text-[var(--maya-text-main)]">
                                    O importar paquete en bodega ({{ availableWarehouseShipments.length }} disponibles):
                                </label>
                                <select
                                    class="mt-1 w-full rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-base)] px-2 py-1 text-xs text-[var(--maya-text-main)]"
                                    @change="(e) => {
                                        const found = availableWarehouseShipments.find(s => s.id === e.target.value);
                                        if (found) importWarehouseShipment(found);
                                        e.target.value = '';
                                    }"
                                >
                                    <option value="">Selecciona para agregar a la ruta...</option>
                                    <option v-for="s in availableWarehouseShipments" :key="s.id" :value="s.id">
                                        {{ s.lpn_code ? `[LPN: ${s.lpn_code}] ` : '' }}{{ s.reference_number ? `[${s.reference_type ? s.reference_type.toUpperCase() : 'DOC'}: ${s.reference_number}] ` : '' }}{{ s.tracking_number }} - {{ s.recipient_name || s.client_name || s.sender?.full_name || 'Destinatario' }} - {{ s.destination_address }} ({{ (s.pieces_count || 1) > 1 ? `${s.pieces_count} bultos · ` : '' }}{{ s.weight_lb }} lbs)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Lista de paradas ordenadas con controles de prioridad (Derecha) -->
                        <div class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm lg:col-span-7">
                            <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-2">
                                <div>
                                    <h3 class="text-sm font-bold text-[var(--maya-text-main)]">
                                        Secuencia de Paradas ({{ wizardForm.items.length }})
                                    </h3>
                                    <p class="text-[11px] text-[var(--maya-text-muted)]">
                                        Ordenadas respetando la regla: <strong class="text-red-500">Alta</strong> → <strong class="text-amber-500">Media</strong> → <strong class="text-gray-500">Baja</strong>.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">{{ priorityCounts.alta }} Altas</span>
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">{{ priorityCounts.media }} Medias</span>
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-700">{{ priorityCounts.baja }} Bajas</span>
                                </div>
                            </div>

                            <div v-if="wizardForm.items.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-sm text-[var(--maya-text-muted)]">
                                <font-awesome-icon :icon="['fas', 'boxes-stacked']" class="mb-2 text-3xl opacity-40" />
                                <p>No has agregado ninguna parada todavía.</p>
                                <p class="text-xs">Usa el formulario de la izquierda para registrar entregas.</p>
                            </div>

                            <div v-else class="mt-3 max-h-[360px] space-y-2 overflow-y-auto pr-1">
                                <div
                                    v-for="(item, index) in wizardForm.items"
                                    :key="item.temp_id || item.id"
                                    class="flex items-center justify-between rounded-xl border p-3 transition"
                                    :class="isPriorityHigh(item) ? 'border-red-200 bg-red-50/40 dark:border-red-900/40 dark:bg-red-950/10' : isPriorityMedium(item) ? 'border-amber-200 bg-amber-50/30 dark:border-amber-900/40 dark:bg-amber-950/10' : 'border-[var(--maya-border)] bg-[var(--maya-hover-surface)]'"
                                >
                                    <div class="flex items-start gap-3">
                                        <!-- Número de parada -->
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[var(--maya-primary)] text-xs font-bold text-white">
                                            {{ item.stop_order }}
                                        </span>

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-[var(--maya-text-main)]">
                                                    {{ item.recipient_name }}
                                                </span>
                                                <span v-if="item.lpn_code || item.shipment?.lpn_code" class="rounded bg-emerald-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                    LPN: {{ item.lpn_code || item.shipment?.lpn_code }}
                                                </span>
                                                <span v-if="item.reference_number || item.shipment?.reference_number" class="rounded bg-sky-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                                                    {{ (item.reference_type || item.shipment?.reference_type || 'Doc').toUpperCase() }}: {{ item.reference_number || item.shipment?.reference_number }}
                                                </span>
                                                <!-- Badge de prioridad -->
                                                <span
                                                    class="rounded px-1.5 py-0.2 text-[10px] font-bold uppercase"
                                                    :class="isPriorityHigh(item) ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : isPriorityMedium(item) ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                                                >
                                                    {{ item.priority_label || item.priority }}
                                                </span>
                                                <span class="font-mono text-[11px] text-[var(--maya-text-muted)]">
                                                    {{ (item.pieces_count || item.shipment?.pieces_count || 1) > 1 ? `${item.pieces_count || item.shipment?.pieces_count} bultos · ` : '' }}{{ item.weight_lb }} lbs ({{ item.package_type }})
                                                </span>
                                            </div>
                                            <p class="text-xs text-[var(--maya-text-muted)] mt-0.5">
                                                📍 {{ item.destination_address }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Acciones de orden y prioridad -->
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-xs text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] disabled:opacity-30"
                                            :disabled="index === 0"
                                            title="Subir orden"
                                            @click="moveStopUp(index)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'arrow-up']" />
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-xs text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)] disabled:opacity-30"
                                            :disabled="index === wizardForm.items.length - 1"
                                            title="Bajar orden"
                                            @click="moveStopDown(index)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'arrow-down']" />
                                        </button>

                                        <button
                                            type="button"
                                            class="ml-1 inline-flex h-7 w-7 items-center justify-center rounded-lg text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30"
                                            title="Eliminar parada"
                                            @click="removeStop(index)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-[var(--maya-border)] pt-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                            @click="prevStep"
                        >
                            <font-awesome-icon :icon="['fas', 'arrow-left']" />
                            Volver a Ruta
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[var(--maya-primary-dark)] disabled:opacity-50"
                            :disabled="wizardForm.items.length === 0"
                            @click="nextStep"
                        >
                            Siguiente: Revisar Plan
                            <font-awesome-icon :icon="['fas', 'arrow-right']" />
                        </button>
                    </div>
                </div>

                <!-- ================================================================ -->
                <!-- PASO 3: RESUMEN Y CONFIRMACIÓN                                   -->
                <!-- ================================================================ -->
                <div v-if="currentStep === 3" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3 text-center">
                            <p class="text-xs text-[var(--maya-text-muted)]">Total Paradas</p>
                            <p class="text-2xl font-extrabold text-[var(--maya-primary)]">{{ wizardForm.items.length }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3 text-center">
                            <p class="text-xs text-[var(--maya-text-muted)]">Carga Total</p>
                            <p class="text-2xl font-extrabold font-mono text-[var(--maya-text-main)]">{{ totalStopsWeightLb.toFixed(1) }} lbs</p>
                        </div>
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3 text-center">
                            <p class="text-xs text-[var(--maya-text-muted)]">Uso de Capacidad</p>
                            <p class="text-2xl font-extrabold text-[var(--maya-text-main)]">{{ loadCapacityPercent }}%</p>
                        </div>
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3 text-center">
                            <p class="text-xs text-[var(--maya-text-muted)]">Prioridades</p>
                            <p class="text-sm font-bold text-[var(--maya-text-main)]">
                                {{ priorityCounts.alta }} A / {{ priorityCounts.media }} M / {{ priorityCounts.baja }} B
                            </p>
                        </div>
                    </div>

                    <!-- Ficha de la Ruta -->
                    <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 text-xs">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div>
                                <span class="text-[var(--maya-text-muted)]">Código:</span>
                                <p class="font-mono font-bold text-black dark:text-white">{{ wizardForm.title }}</p>
                            </div>
                            <div>
                                <span class="text-[var(--maya-text-muted)]">Conductor:</span>
                                <p class="font-semibold text-[var(--maya-text-main)]">
                                    {{ driversList.find(d => d.id == wizardForm.driver_id)?.name || 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-[var(--maya-text-muted)]">Vehículo:</span>
                                <p class="font-semibold text-[var(--maya-text-main)]">
                                    {{ selectedVehicle ? `${selectedVehicle.brand} ${selectedVehicle.model} (${selectedVehicle.license_plate})` : 'Sin asignar' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-[var(--maya-text-muted)]">Salida programada:</span>
                                <p class="font-semibold text-[var(--maya-text-main)]">{{ wizardForm.start_date }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de paradas en secuencia final -->
                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                            Itinerario de Entrega (Secuencia Confirmada)
                        </h4>
                        <div class="max-h-[220px] space-y-1.5 overflow-y-auto">
                            <div
                                v-for="item in wizardForm.items"
                                :key="item.temp_id || item.id"
                                class="flex items-center justify-between rounded-lg border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] px-3 py-2 text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[var(--maya-primary)]">#{{ item.stop_order }}</span>
                                    <span class="font-semibold text-[var(--maya-text-main)]">{{ item.recipient_name }}</span>
                                    <span v-if="item.lpn_code || item.shipment?.lpn_code" class="rounded bg-emerald-100 px-1.5 py-0.2 font-mono text-[9px] font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        LPN: {{ item.lpn_code || item.shipment?.lpn_code }}
                                    </span>
                                    <span v-if="item.reference_number || item.shipment?.reference_number" class="rounded bg-sky-100 px-1.5 py-0.2 font-mono text-[9px] font-bold text-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                                        {{ (item.reference_type || item.shipment?.reference_type || 'Doc').toUpperCase() }}: {{ item.reference_number || item.shipment?.reference_number }}
                                    </span>
                                    <span class="text-[var(--maya-text-muted)]">- {{ item.destination_address }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[11px]">{{ item.weight_lb }} lbs</span>
                                    <span
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                        :class="isPriorityHigh(item) ? 'bg-red-100 text-red-700' : isPriorityMedium(item) ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-700'"
                                    >
                                        {{ item.priority_label || item.priority }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-[var(--maya-border)] pt-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                            @click="prevStep"
                        >
                            <font-awesome-icon :icon="['fas', 'arrow-left']" />
                            Modificar Paradas
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
                            :disabled="savingTask"
                            @click="submitPlan"
                        >
                            <font-awesome-icon v-if="savingTask" :icon="['fas', 'clock']" class="animate-spin" />
                            <font-awesome-icon v-else :icon="['fas', 'check']" />
                            {{ savingTask ? 'Creando Plan...' : 'Confirmar y Crear Plan (Pendiente)' }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ==================================================================== -->
        <!-- MODAL DE DETALLE DEL PLAN Y REORDENAMIENTO                          -->
        <!-- ==================================================================== -->
        <Modal :show="detailOpen" max-width="3xl" @close="closeDetailModal">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-3">
                    <div v-if="detailTask">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-black dark:text-white">{{ detailTask.title }}</span>
                            <span
                                v-if="isTaskInProgress(detailTask)"
                                class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-500 opacity-75" />
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-600" />
                                </span>
                                En curso &bull; {{ formatElapsedTime(detailTask.start_date_raw || detailTask.start_date) }}
                            </span>
                            <span
                                v-else-if="isTaskCompleted(detailTask)"
                                class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700 dark:bg-green-900/40 dark:text-green-300"
                            >
                                <font-awesome-icon :icon="['fas', 'check']" />
                                Finalizado ({{ detailTask.total_hours || 0 }} hrs)
                            </span>
                        </div>
                        <h2 class="text-base font-bold text-[var(--maya-text-main)]">
                            Detalle de Plan de Entrega
                        </h2>
                    </div>
                    <button type="button" class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]" @click="closeDetailModal">
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <div v-if="loadingDetail" class="py-8 text-center text-sm text-[var(--maya-text-muted)]">
                    Cargando información del plan...
                </div>

                <div v-else-if="detailTask" class="mt-4 space-y-4">
                    <!-- Ficha técnica -->
                    <div class="grid grid-cols-2 gap-3 rounded-xl border border-[var(--maya-border)] bg-[var(--maya-hover-surface)] p-3 text-xs sm:grid-cols-4">
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Conductor:</span>
                            <p class="font-semibold">{{ detailTask.driver_name }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Vehículo:</span>
                            <p class="font-semibold">{{ detailTask.vehicle_label }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Bodega Salida:</span>
                            <p class="font-semibold">{{ detailTask.warehouse_name }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--maya-text-muted)]">Salida:</span>
                            <p class="font-semibold">{{ detailTask.start_date || 'Inmediata' }}</p>
                        </div>
                    </div>

                    <!-- Métricas de paradas -->
                    <div class="grid grid-cols-4 gap-2 text-center text-xs">
                        <div class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-2">
                            <span class="text-[11px] text-[var(--maya-text-muted)]">Total Paradas</span>
                            <p class="text-base font-bold text-[var(--maya-text-main)]">{{ detailTask.total_items }}</p>
                        </div>
                        <div class="rounded-xl border border-green-200 bg-green-50/50 p-2 dark:border-green-900/40 dark:bg-green-950/20">
                            <span class="text-[11px] text-green-700 dark:text-green-400">Entregados</span>
                            <p class="text-base font-bold text-green-700 dark:text-green-300">{{ detailTask.delivered_count || 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-2 dark:border-blue-900/40 dark:bg-blue-950/20">
                            <span class="text-[11px] text-blue-700 dark:text-blue-400">En Ruta / Pend.</span>
                            <p class="text-base font-bold text-blue-700 dark:text-blue-300">{{ detailTask.pending_count || 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-red-200 bg-red-50/50 p-2 dark:border-red-900/40 dark:bg-red-950/20">
                            <span class="text-[11px] text-red-700 dark:text-red-400">Retornados</span>
                            <p class="text-base font-bold text-red-700 dark:text-red-300">{{ detailTask.returned_count || 0 }}</p>
                        </div>
                    </div>

                    <!-- Lista de Paradas -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--maya-text-muted)]">
                                Paradas de la Ruta ({{ detailTask.items?.length || 0 }})
                            </h4>
                            <span v-if="isTaskPending(detailTask)" class="text-[11px] text-[var(--maya-text-muted)]">
                                Puedes reordenar paradas respetando la prioridad
                            </span>
                            <span v-else-if="isTaskInProgress(detailTask)" class="text-[11px] font-semibold text-blue-600 dark:text-blue-400">
                                Puedes registrar entregas y retornos en tiempo real
                            </span>
                        </div>

                        <div class="max-h-[340px] space-y-2 overflow-y-auto pr-1">
                            <div
                                v-for="(item, idx) in detailTask.items"
                                :key="item.id"
                                class="flex items-center justify-between rounded-xl border border-[var(--maya-border)] p-3 text-xs transition-colors"
                                :class="{
                                    'bg-green-50/30 dark:bg-green-950/10 border-green-200 dark:border-green-900/40': isItemDelivered(item),
                                    'bg-red-50/30 dark:bg-red-950/10 border-red-200 dark:border-red-900/40': isItemReturned(item),
                                    'hover:bg-[var(--maya-hover-surface)]': isItemPending(item)
                                }"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-bold text-white text-[11px]"
                                        :class="isItemDelivered(item) ? 'bg-green-600' : isItemReturned(item) ? 'bg-red-600' : 'bg-[var(--maya-primary)]'"
                                    >
                                        {{ item.stop_order }}
                                    </span>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-[var(--maya-text-main)]">{{ item.shipment?.recipient_name || item.shipment?.sender_name || 'Destinatario' }}</span>
                                            <span v-if="item.shipment?.lpn_code" class="rounded bg-emerald-100 px-1.5 py-0.2 font-mono text-[9px] font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                LPN: {{ item.shipment.lpn_code }}
                                            </span>
                                            <span v-if="item.shipment?.reference_number" class="rounded bg-sky-100 px-1.5 py-0.2 font-mono text-[9px] font-bold text-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                                                {{ (item.shipment?.reference_type || 'Doc').toUpperCase() }}: {{ item.shipment.reference_number }}
                                            </span>
                                            <span
                                                class="rounded px-1.5 py-0.2 text-[10px] font-bold uppercase"
                                                :class="isPriorityHigh(item) ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : isPriorityMedium(item) ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                                            >
                                                {{ item.priority_label || item.priority }}
                                            </span>
                                            <span class="font-mono text-[11px] text-[var(--maya-primary)]">
                                                {{ item.shipment?.tracking_number }}
                                            </span>
                                            <span v-if="item.shipment?.weight_lb" class="font-mono text-[10px] text-[var(--maya-text-muted)]">
                                                {{ (item.shipment?.pieces_count || 1) > 1 ? `${item.shipment.pieces_count} bultos · ` : '' }}({{ item.shipment.weight_lb }} lbs)
                                            </span>
                                        </div>
                                        <p class="text-[var(--maya-text-muted)] mt-0.5">
                                            📍 {{ item.shipment?.destination_address }}
                                        </p>

                                        <!-- Estado de parada -->
                                        <div class="mt-1 flex items-center gap-2">
                                            <span
                                                v-if="isItemDelivered(item)"
                                                class="inline-flex items-center gap-1 font-semibold text-green-700 dark:text-green-400 text-[11px]"
                                            >
                                                <font-awesome-icon :icon="['fas', 'check']" />
                                                Entregado {{ item.delivered_at ? `(${item.delivered_at})` : '' }}
                                            </span>
                                            <span
                                                v-else-if="isItemReturned(item)"
                                                class="inline-flex items-center gap-1 font-semibold text-red-700 dark:text-red-400 text-[11px]"
                                            >
                                                <font-awesome-icon :icon="['fas', 'rotate-left']" />
                                                Devuelto: {{ item.return_reason || 'Sin motivo' }}
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 text-[var(--maya-text-muted)] text-[11px]"
                                            >
                                                <font-awesome-icon :icon="['fas', 'clock']" class="text-[9px]" />
                                                Pendiente de entrega
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones por Parada -->
                                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                    <!-- Si está en curso y la parada está pendiente -->
                                    <template v-if="isTaskInProgress(detailTask) && isItemPending(item)">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-green-700 shadow-sm"
                                            title="Confirmar Entrega"
                                            @click="markStopDelivered(item)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'check']" />
                                            Entregar
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 text-red-600 px-2 py-1 text-xs hover:bg-red-50 dark:hover:bg-red-900/20"
                                            title="Registrar Intento Fallido"
                                            @click="openReturnStopModal(item)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'rotate-left']" />
                                            Retorno
                                        </button>
                                    </template>

                                    <!-- Si está pendiente (reordenar y desasignar) -->
                                    <template v-else-if="isTaskPending(detailTask)">
                                        <button
                                            type="button"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border border-[var(--maya-border)] text-xs disabled:opacity-30"
                                            :disabled="idx === 0"
                                            title="Subir parada"
                                            @click="moveDetailStopUp(idx)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'arrow-up']" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border border-[var(--maya-border)] text-xs disabled:opacity-30"
                                            :disabled="idx === detailTask.items.length - 1"
                                            title="Bajar parada"
                                            @click="moveDetailStopDown(idx)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'arrow-down']" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded border border-red-200 text-red-600 hover:bg-red-50 text-xs"
                                            title="Desasignar de este plan"
                                            @click="unassignStopFromTask(item)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer del Modal -->
                    <div class="flex items-center justify-between border-t border-[var(--maya-border)] pt-4">
                        <div>
                            <button
                                v-if="isTaskPending(detailTask)"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
                                @click="confirmStartTask(detailTask)"
                            >
                                <font-awesome-icon :icon="['fas', 'truck']" />
                                Iniciar Ruta Ahora
                            </button>
                            <button
                                v-else-if="isTaskInProgress(detailTask)"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-green-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-green-700"
                                @click="confirmCompleteTask(detailTask)"
                            >
                                <font-awesome-icon :icon="['fas', 'check']" />
                                Finalizar Ruta Completa
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="isTaskPending(detailTask)"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                                :disabled="savingReorder"
                                @click="saveDetailReorder"
                            >
                                {{ savingReorder ? 'Guardando...' : 'Guardar Reordenamiento' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                                @click="closeDetailModal"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ==================================================================== -->
        <!-- MODAL DE CANCELACIÓN DE PLAN                                         -->
        <!-- ==================================================================== -->
        <Modal :show="cancelModalOpen" max-width="md" @close="cancelModalOpen = false">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-3">
                    <h3 class="text-base font-bold text-red-600">Cancelar Plan de Entrega</h3>
                    <button type="button" class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]" @click="cancelModalOpen = false">
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <p class="text-[var(--maya-text-main)]">
                        ¿Estás seguro de cancelar el plan <strong class="font-mono">{{ taskToCancel?.title }}</strong>?
                        Todos los paquetes asignados que no hayan sido entregados retornarán automáticamente a la bodega de origen.
                    </p>

                    <div>
                        <label class="block font-medium text-[var(--maya-text-main)] mb-1">Motivo de cancelación (opcional):</label>
                        <textarea
                            v-model="cancelReason"
                            rows="3"
                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-2 text-xs text-[var(--maya-text-main)] focus:ring-1 focus:ring-[var(--maya-primary)]"
                            placeholder="Ej. Avería de vehículo, condiciones climáticas adversas, reprogramación solicitada..."
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 border-t border-[var(--maya-border)] pt-3">
                    <button
                        type="button"
                        class="rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="cancelModalOpen = false"
                    >
                        Volver
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50"
                        :disabled="cancellingTask"
                        @click="submitCancelTask"
                    >
                        {{ cancellingTask ? 'Cancelando...' : 'Confirmar Cancelación' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ==================================================================== -->
        <!-- MODAL DE RETORNO DE PARADA                                           -->
        <!-- ==================================================================== -->
        <Modal :show="returnStopModalOpen" max-width="md" @close="returnStopModalOpen = false">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-[var(--maya-border)] pb-3">
                    <h3 class="text-base font-bold text-[var(--maya-text-main)]">Registrar Parada Fallida / Retorno</h3>
                    <button type="button" class="text-[var(--maya-text-muted)] hover:text-[var(--maya-text-main)]" @click="returnStopModalOpen = false">
                        <font-awesome-icon :icon="['fas', 'xmark']" />
                    </button>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <p class="text-[var(--maya-text-muted)]">
                        Parada #{{ stopToReturn?.stop_order }} &bull; Destinatario: <strong>{{ stopToReturn?.shipment?.sender_name }}</strong>
                    </p>

                    <div>
                        <label class="block font-medium text-[var(--maya-text-main)] mb-1">Motivo del Retorno:</label>
                        <select
                            v-model="selectedReturnReason"
                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-2 text-xs text-[var(--maya-text-main)]"
                        >
                            <option value="Destinatario ausente">Destinatario ausente</option>
                            <option value="Dirección incorrecta o no localizada">Dirección incorrecta o no localizada</option>
                            <option value="Rehusado por el cliente">Rehusado por el cliente</option>
                            <option value="Zona inaccesible / Peligro">Zona inaccesible / Peligro</option>
                            <option value="Otro">Otro motivo (especificar)</option>
                        </select>
                    </div>

                    <div v-if="selectedReturnReason === 'Otro'">
                        <label class="block font-medium text-[var(--maya-text-main)] mb-1">Detalle del motivo:</label>
                        <input
                            v-model="customReturnReason"
                            type="text"
                            class="w-full rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-2 text-xs text-[var(--maya-text-main)]"
                            placeholder="Especificar motivo..."
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 border-t border-[var(--maya-border)] pt-3">
                    <button
                        type="button"
                        class="rounded-xl border border-[var(--maya-border)] px-4 py-2 text-xs font-medium text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="returnStopModalOpen = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50"
                        :disabled="returningStop"
                        @click="submitReturnStop"
                    >
                        {{ returningStop ? 'Guardando...' : 'Registrar Retorno' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
