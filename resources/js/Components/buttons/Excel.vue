<script setup>
import { ref } from 'vue';
import XlsxWriter from '@/Services/XlsxWriter.js';

const props = defineProps({

    columns: {
        type: Array,
        required: true,
    },


    fetchAllData: {
        type: Function,
        required: true,
    },


    moduleName: {
        type: String,
        required: true,
    },


    loading: {
        type: Boolean,
        default: false,
    },


    label: {
        type: String,
        default: 'Exportar Excel',
    },


    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'success'].includes(v),
    },
});

const emit = defineEmits(['export-start', 'export-end', 'export-error']);

const exporting = ref(false);

const variantClasses = {
    default:
        'border-[var(--maya-border)] bg-[var(--maya-bg-surface)] text-[var(--maya-text-main)] hover:bg-[var(--maya-hover-surface)]',
    success:
        'border-[var(--maya-success)] bg-[var(--maya-success)] text-white hover:opacity-90',
};

const handleExport = async () => {
    if (exporting.value) return;

    exporting.value = true;
    emit('export-start');

    try {
        const allData = await props.fetchAllData();

        if (!allData || allData.length === 0) {
            console.warn('[Excel Export] No data returned from fetchAllData');
            exporting.value = false;
            emit('export-end');
            return;
        }

        const writer = new XlsxWriter();
        const zipData = writer.generate(props.columns, allData);

        // Build filename: moduleName_YYYY-MM-DD.xlsx
        const date = new Date().toISOString().split('T')[0];
        const filename = `${props.moduleName}_${date}.xlsx`;

        // Trigger download
        const blob = new Blob([zipData], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);

        // Clean up the object URL after a short delay (allows browser to start download)
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (error) {
        console.error('[Excel Export Error]', error);
        emit('export-error', error);
    } finally {
        exporting.value = false;
        emit('export-end');
    }
};
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-base)] disabled:cursor-not-allowed disabled:opacity-50"
        :class="[variantClasses[variant]]"
        :disabled="exporting || loading"
        @click="handleExport"
    >
        <font-awesome-icon
            :icon="['fas', 'file-excel']"
            :class="exporting ? 'animate-pulse' : ''"
        />
        <span>{{ exporting ? 'Exportando...' : label }}</span>
    </button>
</template>
