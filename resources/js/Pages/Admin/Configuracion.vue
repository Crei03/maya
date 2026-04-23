<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SettingsSectionButton from '@/Components/Admin/SettingsSectionButton.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import ColumnVisibilitySelector from '@/Components/Admin/ColumnVisibilitySelector.vue';
import RefreshButton from '@/Components/Admin/RefreshButton.vue';
import ModalForm from '@/Components/Admin/ModalForm.vue';

const columnPreferenceModule = 'admin.clients';
const activeSection = ref(null);
const showFilters = ref(false);
const currentPage = ref(1);
const perPage = ref(15);

const sections = [
    { key: 'clientes', label: 'Clientes', icon: ['fas', 'user'], route: route('admin.configuracion.clientes') },
    { key: 'transporte', label: 'Transporte', icon: ['fas', 'truck'] },
    { key: 'conductor', label: 'Conductor', icon: ['fas', 'id-card'] },
    { key: 'roles', label: 'Roles', icon: ['fas', 'shield-alt'] },
];

const selectSection = (section) => {
    if (section.route) {
        router.get(section.route);
        return;
    }

    activeSection.value = section.key;
};

</script>

<template>
    <Head title="Configuracion" />

    <AdminLayout title="Configuracion">
        <div class="space-y-6">
            <section
                v-if="!activeSection"
                class="rounded-2xl border border-[var(--maya-border)] bg-[var(--maya-bg-surface)] p-4 shadow-sm"
            >
                <h2 class="text-base font-semibold text-[var(--maya-text-main)]">Secciones</h2>
                <p class="mt-1 text-sm text-[var(--maya-text-muted)]">
                    Selecciona la seccion que deseas administrar.
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <SettingsSectionButton
                        v-for="section in sections"
                        :key="section.key"
                        :label="section.label"
                        :icon="section.icon"
                        :active="false"
                        @click="selectSection(section)"
                    />
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
