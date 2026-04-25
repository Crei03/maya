<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Filters from '@/Components/buttons/Filters.vue';
import RefreshButton from '@/Components/buttons/RefreshButton.vue';
import ModalForm from '@/Components/ModalForm.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDateOnly } = useDateFormat();

const activeSection = ref(null);
const showFilters = ref(false);
const currentPage = ref(1);
const perPage = ref(15);

const sections = [
    { key: 'roles', label: 'Roles' },
];

const activeSectionTitle = 'Usuarios';

const columns = [
    { key: 'name', label: 'Usuario' },
    { key: 'email', label: 'Correo' },
    { key: 'status', label: 'Estado' },
    { key: 'created_at', label: 'Fecha de creación' },
];

const defaultVisibleColumns = columns.map((column) => column.key);

const visibleColumns = ref([...defaultVisibleColumns]);

const loading = ref(false);
const saving = ref(false);
const users = ref([]);
const pagination = ref(null);
const successMessage = ref('');

const filters = reactive({
    search: '',
});

const modalOpen = ref(false);
const errors = ref({});
const editingId = ref(null);

const form = reactive({
    name: '',
    email: '',
    password: '',
    status: true,
});

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Buscar usuario o correo' },
];

const userFormFields = computed(() => [
    { key: 'name', label: 'Usuario', type: 'text', placeholder: 'Ingresa el nombre de usuario' },
    { key: 'email', label: 'Correo', type: 'text', placeholder: 'Ingresa el correo electrónico' },
    { key: 'password', label: editingId.value ? 'Nueva contraseña (opcional)' : 'Contraseña', type: 'text', placeholder: editingId.value ? 'Dejar vacío para mantener' : 'Mínimo 8 caracteres', isPassword: true },
    { key: 'status', label: 'Activo', type: 'switch', placeholder: '' },
]);

const toolbarSubtitle = computed(() => {
    if (!pagination.value) {
        return 'Sin datos cargados';
    }

    return `Mostrando ${pagination.value.from || 0}-${pagination.value.to || 0} de ${pagination.value.total || 0} usuarios`;
});

const backToSections = () => {
    router.get(route('admin.configuracion'));
};

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.password = '';
    form.status = true;
    errors.value = {};
    editingId.value = null;
};

const openModal = () => {
    successMessage.value = '';
    resetForm();
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

const openEditModal = async (user) => {
    successMessage.value = '';
    editingId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.status = user.status;
    errors.value = {};
    modalOpen.value = true;
};

const fetchUsers = async () => {
    loading.value = true;

    try {
        const response = await window.axios.get(route('admin.users.list'), {
            params: {
                search: filters.search || undefined,
                page: currentPage.value,
                per_page: perPage.value,
            },
        });

        const payload = response.data?.data;
        users.value = payload?.data || [];
        pagination.value = payload || null;

        if (payload?.current_page) {
            currentPage.value = Number(payload.current_page);
        }

        if (payload?.per_page) {
            perPage.value = Number(payload.per_page);
        }
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    currentPage.value = 1;
    await fetchUsers();
};

const clearFilters = async () => {
    filters.search = '';
    currentPage.value = 1;
    await fetchUsers();
};

const handlePerPageChange = async (value) => {
    const nextValue = Math.max(1, Math.min(200, Number(value) || perPage.value));
    perPage.value = nextValue;
    currentPage.value = 1;
    await fetchUsers();
};

const handlePageChange = async (page) => {
    const nextPage = Math.max(1, Number(page) || 1);
    currentPage.value = nextPage;
    await fetchUsers();
};

const createUser = async () => {
    saving.value = true;
    errors.value = {};

    try {
        await window.axios.post(route('admin.users.store'), {
            name: form.name,
            email: form.email,
            password: form.password,
            status: form.status,
        });

        successMessage.value = 'Usuario creado correctamente.';
        closeModal();
        await fetchUsers();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }

        errors.value = {
            form: ['No fue posible guardar el usuario. Intenta nuevamente.'],
        };
    } finally {
        saving.value = false;
    }
};

const updateUser = async () => {
    saving.value = true;
    errors.value = {};

    try {
        await window.axios.patch(route('admin.users.update', { id: editingId.value }), {
            name: form.name,
            email: form.email,
            password: form.password || undefined,
            status: form.status,
        });

        successMessage.value = 'Usuario actualizado correctamente.';
        closeModal();
        await fetchUsers();
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            return;
        }

        errors.value = {
            form: ['No fue posible actualizar el usuario. Intenta nuevamente.'],
        };
    } finally {
        saving.value = false;
    }
};

const deleteUser = async (id) => {
    if (!confirm('¿Estás seguro de eliminar este usuario?')) {
        return;
    }

    try {
        await window.axios.delete(route('admin.users.destroy', { id }));
        successMessage.value = 'Usuario eliminado correctamente.';
        await fetchUsers();
    } catch (error) {
        alert('No fue posible eliminar el usuario. Intenta nuevamente.');
    }
};

onMounted(async () => {
    await fetchUsers();
});
</script>

<template>
    <Head title="Usuarios" />

    <AdminLayout title="Usuarios">
        <section
            :key="activeSection"
            class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm"
        >
            <div class="mb-4 flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                    @click="backToSections"
                >
                    <font-awesome-icon :icon="['fas', 'arrow-left']" />
                </button>
                <h2 class="text-base font-semibold text-[var(--maya-text-main)]">{{ activeSectionTitle }}</h2>
            </div>

            <section v-if="activeSection === 'roles'" class="rounded-xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-6">
                <h3 class="text-lg font-semibold text-[var(--maya-text-main)]">Configuración de roles</h3>
                <p class="mt-2 text-sm text-[var(--maya-text-muted)]">
                    Define permisos y acceso para roles del ecosistema MAYA.
                </p>
            </section>

            <section v-else class="space-y-4">
                <div v-if="successMessage" class="mt-3 rounded-md border border-[var(--maya-success)] bg-[var(--maya-success-alpha)] px-3 py-2 text-sm text-[var(--maya-success-dark)]">
                    {{ successMessage }}
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] px-3 py-2 text-xs font-semibold text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]"
                        @click="showFilters = !showFilters"
                    >
                        <font-awesome-icon :icon="['fas', 'filter']" />
                        {{ showFilters ? 'Ocultar filtros' : 'Filtro' }}
                    </button>

                    <RefreshButton :loading="loading" @refresh="fetchUsers" />

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold text-white hover:bg-[var(--maya-primary-dark)]"
                        @click="openModal"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        Agregar usuario
                    </button>
                </div>

                <Filters
                    v-if="showFilters"
                    :fields="filterFields"
                    :model-value="filters"
                    @update:model-value="Object.assign(filters, $event)"
                    @apply="applyFilters"
                    @clear="clearFilters"
                />

                <DataTable
                    :columns="columns"
                    :rows="users"
                    :visible-columns="visibleColumns"
                    :loading="loading"
                    :pagination="pagination"
                    :per-page="perPage"
                    :min-per-page="1"
                    :max-per-page="200"
                    empty-text="No hay usuarios creados todavía."
                    @update:per-page="handlePerPageChange"
                    @change-page="handlePageChange"
                    @edit="openEditModal"
                    @delete="deleteUser"
                >
                    <template #cell-status="{ row }">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="row.status
                                ? 'bg-[var(--maya-success-alpha)] text-[var(--maya-success-dark)]'
                                : 'bg-[var(--maya-danger-alpha)] text-[var(--maya-danger-dark)]'"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="row.status ? 'bg-[var(--maya-success)]' : 'bg-[var(--maya-danger)]'"
                            />
                            {{ row.status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </template>

                    <template #cell-created_at="{ row }">
                        <span class="text-sm text-[var(--maya-text-main)]">
                            {{ formatDateOnly(row.created_at) }}
                        </span>
                    </template>
                </DataTable>
            </section>
        </section>

        <ModalForm
            :model-value="form"
            :show="modalOpen"
            :title="editingId ? 'Editar usuario' : 'Nuevo usuario'"
            :description="editingId ? 'Completa los datos para actualizar el usuario.' : 'Completa los datos para registrar un nuevo usuario en el sistema.'"
            :fields="userFormFields"
            :errors="errors"
            :loading="saving"
            :submit-label="editingId ? 'Actualizar usuario' : 'Guardar usuario'"
            :columns="1"
            @update:model-value="Object.assign(form, $event)"
            @close="closeModal"
            @submit="editingId ? updateUser() : createUser()"
        />
    </AdminLayout>
</template>
