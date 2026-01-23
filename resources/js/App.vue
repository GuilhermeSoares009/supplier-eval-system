<template>
    <div class="min-h-screen bg-background-light">
        <Toast position="top-right" appendTo="body" />
        <ConfirmDialog appendTo="body" />

        <header class="sticky top-0 z-40 border-b border-border bg-surface">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-6 lg:px-10 py-3 text-ink">
                <div class="size-6 text-primary">
                    <svg fill="currentColor" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z"></path>
                    </svg>
                </div>
                <h1 class="text-sm font-semibold leading-tight">Supplier Evaluation Dashboard</h1>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 lg:px-10 py-8 space-y-8">
            <section>
                <div
                    class="flex flex-col gap-4 rounded-xl border-2 border-dashed border-[#d7e3f5] bg-[#f8fbff] p-8 text-center items-center justify-center"
                    :class="{ 'border-primary bg-primary/5': isDragging }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                >
                    <div class="flex flex-col gap-2">
                        <h2 class="text-sm font-semibold">Smart Import</h2>
                        <p class="text-xs text-muted">Drag and drop your RIR data files here to start the evaluation process.</p>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <div
                            v-for="file in files"
                            :key="file.name"
                            class="flex h-9 items-center justify-center gap-x-2 rounded-full bg-[#eef4ff] border border-[#d6e2ff] px-4"
                        >
                            <span class="pi pi-check-circle text-primary text-sm"></span>
                            <p class="text-primary text-sm font-semibold">{{ file.name }}</p>
                            <span class="pi pi-times text-primary text-sm cursor-pointer" @click="removeFile(file)"></span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <label class="inline-flex">
                            <input ref="fileInput" type="file" multiple class="hidden" @change="handleFileInput" />
                            <span class="cursor-pointer rounded-lg bg-white px-4 py-2 text-xs font-semibold border border-border hover:bg-background-light">
                                Browse files
                            </span>
                        </label>
                        <Button
                            class="rounded-lg bg-primary text-white px-4 py-2 text-xs font-semibold hover:bg-primary/90"
                            :disabled="files.length === 0 || isUploading"
                            @click="uploadFiles"
                        >
                            <span v-if="!isUploading">Importar</span>
                            <span v-else class="inline-flex items-center gap-2">
                                <ProgressSpinner style="width: 16px; height: 16px" strokeWidth="6" />
                                Importing
                            </span>
                        </Button>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <h2 class="text-base font-semibold">Monthly Consolidated Analysis</h2>
                    <div class="flex items-center gap-2 rounded-full border border-border bg-surface px-4 py-1.5 shadow-sm">
                        <span class="text-[11px] text-muted font-medium">Filter:</span>
                        <Dropdown
                            v-model="selectedMonth"
                            :options="monthOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select month"
                            appendTo="body"
                            scrollHeight="240px"
                            @change="loadDashboard"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
                        <div class="max-h-[420px] overflow-y-auto">
                            <DataTable :value="dashboard" stripedRows>
                            <Column field="fornecedor" header="SUPPLIER"></Column>
                            <Column field="otimo" header="ÓTIMO"></Column>
                            <Column field="bom" header="BOM"></Column>
                            <Column field="regular" header="REGULAR"></Column>
                            <Column field="insatisfatorio" header="INSATISFATÓRIO"></Column>
                            </DataTable>
                        </div>
                    </div>

                    <div class="flex flex-col gap-5 p-6 rounded-xl border border-border bg-surface shadow-sm">
                        <div class="flex justify-between items-center">
                            <h3 class="text-[10px] font-extrabold uppercase tracking-wide text-muted">Distribution by supplier</h3>
                            <div class="flex gap-3 text-[9px] font-extrabold uppercase tracking-wide text-muted">
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-tertiary rounded-sm"></i> ÓTIMO</span>
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-primary rounded-sm"></i> BOM</span>
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-secondary rounded-sm"></i> REG</span>
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-error rounded-sm"></i> INSAT</span>
                            </div>
                        </div>

                        <div class="space-y-4 max-h-[420px] overflow-y-auto pr-2">
                            <div v-for="linha in dashboard" :key="linha.fornecedor" class="space-y-2">
                            <div class="flex justify-between text-xs font-medium">
                                <span>{{ linha.fornecedor }}</span>
                                <span class="text-muted">{{ linha.total }} total</span>
                            </div>
                            <div class="w-full h-8 flex rounded-lg overflow-hidden bg-background-light border border-border/60">
                                <div class="bg-tertiary" :style="{ width: percent(linha.otimo, linha.total) }"></div>
                                <div class="bg-primary" :style="{ width: percent(linha.bom, linha.total) }"></div>
                                <div class="bg-secondary" :style="{ width: percent(linha.regular, linha.total) }"></div>
                                <div class="bg-error" :style="{ width: percent(linha.insatisfatorio, linha.total) }"></div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between px-4 flex-wrap gap-3">
                    <h2 class="text-base font-semibold">Yearly Performance Heatmap</h2>
                    <Button
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90"
                        icon="pi pi-download"
                        @click="exportarAvaliacao"
                    >
                        Export Excel
                    </Button>
                </div>
                <div class="p-6 rounded-xl border border-border bg-surface shadow-sm overflow-x-auto">
                    <div class="min-w-[800px]">
                        <div class="grid grid-cols-13 gap-1 mb-4 text-[10px] font-bold uppercase tracking-wide text-muted">
                            <div>Supplier</div>
                            <div v-for="mes in meses" :key="mes" class="text-center">{{ mesesLabel[mes] || mes }}</div>
                        </div>

                        <div v-for="linha in heatmap" :key="linha.fornecedor" class="grid grid-cols-13 gap-1 items-center mb-2">
                            <div class="text-sm font-medium">{{ linha.fornecedor }}</div>
                            <div
                                v-for="mes in meses"
                                :key="mes"
                                class="h-7 rounded-md"
                                :class="statusClass(linha.meses[mes])"
                                :title="linha.meses[mes] || 'Sem dados'"
                            ></div>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-6 text-[10px] text-muted font-bold uppercase tracking-wide">
                        <div class="flex items-center gap-2"><i class="w-3 h-3 bg-tertiary rounded-sm"></i> HIGH QUALITY</div>
                        <div class="flex items-center gap-2"><i class="w-3 h-3 bg-error rounded-sm"></i> CRITICAL RISK</div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-border bg-surface">
            <div class="mx-auto flex max-w-5xl flex-col sm:flex-row items-center justify-between gap-3 px-6 lg:px-10 py-6">
                <p class="text-xs text-muted">© 2026 Quality Management Systems — Internal Supplier Audit Tool</p>
                <button class="text-sm font-semibold text-error hover:underline" @click="confirmarLimpeza">
                    Clear data
                </button>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';

const toast = useToast();
const confirm = useConfirm();
const files = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const dashboard = ref([]);
const heatmap = ref([]);
const currentYear = new Date().getFullYear();
const selectedYear = ref(currentYear);
const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const mesesLabel = {
    Jan: 'Jan',
    Fev: 'Feb',
    Mar: 'Mar',
    Abr: 'Apr',
    Mai: 'May',
    Jun: 'Jun',
    Jul: 'Jul',
    Ago: 'Aug',
    Set: 'Sep',
    Out: 'Oct',
    Nov: 'Nov',
    Dez: 'Dec',
};
const monthOptions = ref([]);
const selectedMonth = ref('');

const handleFileInput = (event) => {
    const selected = Array.from(event.target.files || []);
    files.value = mergeFiles(files.value, selected);
};

const handleDrop = (event) => {
    isDragging.value = false;
    const dropped = Array.from(event.dataTransfer.files || []);
    files.value = mergeFiles(files.value, dropped);
};

const mergeFiles = (existing, incoming) => {
    const map = new Map();
    [...existing, ...incoming].forEach((file) => map.set(file.name, file));
    return Array.from(map.values());
};

const removeFile = (fileToRemove) => {
    files.value = files.value.filter((file) => file.name !== fileToRemove.name);
};

const uploadFiles = async () => {
    if (files.value.length === 0) return;
    const formData = new FormData();
    files.value.forEach((file) => formData.append('arquivos[]', file));

    try {
        isUploading.value = true;
        const { data } = await axios.post('/api/importar-rir', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        toast.add({
            severity: 'success',
            summary: 'Importação concluída',
            detail: `Registros importados: ${data.importados}`,
            life: 4000,
        });
        files.value = [];
        await Promise.all([loadDashboard(), loadHeatmap()]);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Falha na importação',
            detail: error.response?.data?.message || 'Não foi possível importar os arquivos.',
            life: 5000,
        });
    } finally {
        isUploading.value = false;
    }
};

const loadDashboard = async () => {
    const params = selectedMonth.value ? { mes: selectedMonth.value } : {};
    const { data } = await axios.get('/api/dashboard-mensal', { params });
    dashboard.value = data.fornecedores;
    if (Array.isArray(data.meses) && data.meses.length > 0) {
        monthOptions.value = data.meses.map((mes) => {
            const [ano, mesNumero] = mes.split('-');
            const indice = Number(mesNumero) - 1;
            const chave = meses[indice];
            const nome = chave ? (mesesLabel[chave] || chave) : null;
            const label = nome ? `${nome} ${ano}` : mes;
            return { label, value: mes };
        });
        selectedMonth.value = data.mes;
    } else if (!selectedMonth.value) {
        selectedMonth.value = data.mes;
        monthOptions.value = [{ label: data.mes, value: data.mes }];
    }
};

const loadHeatmap = async () => {
    const params = selectedYear.value ? { ano: selectedYear.value } : {};
    const { data } = await axios.get('/api/heatmap-anual', { params });
    heatmap.value = data.fornecedores;
    selectedYear.value = data.ano;
};

const exportarAvaliacao = async () => {
    try {
        const response = await axios.get('/api/exportar-avaliacao', {
            params: { ano: selectedYear.value },
            responseType: 'blob',
        });
        const blob = new Blob([response.data], { type: response.headers['content-type'] });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `AVALIACAO_FORNECEDORES_${selectedYear.value}.xlsx`;
        link.click();
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Falha na exportação',
            detail: 'Não foi possível gerar o arquivo XLSX.',
            life: 5000,
        });
    }
};

const confirmarLimpeza = () => {
    confirm.require({
        message: 'Tem certeza que deseja apagar todos os registros? Esta ação é irreversível.',
        header: 'Confirmação de limpeza',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Apagar tudo',
        acceptClass: 'bg-error text-white px-4 py-2 rounded-xl motion-soft',
        rejectClass: 'bg-surface text-muted px-4 py-2 rounded-xl border border-border motion-soft',
        accept: async () => {
            try {
                await axios.post('/api/limpar-dados');
                dashboard.value = [];
                heatmap.value = [];
                monthOptions.value = [];
                selectedMonth.value = '';
                await Promise.all([loadDashboard(), loadHeatmap()]);
                toast.add({
                    severity: 'success',
                    summary: 'Base limpa',
                    detail: 'Todos os registros foram removidos com sucesso.',
                    life: 4000,
                });
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Falha ao limpar',
                    detail: error.response?.data?.message || 'Não foi possível remover os registros.',
                    life: 5000,
                });
            }
        },
    });
};

const percent = (value, total) => {
    if (!total) return '0%';
    return `${Math.round((value / total) * 100)}%`;
};

const statusClass = (status) => {
    switch (status) {
        case 'Ótimo':
            return 'status-otimo';
        case 'Bom':
            return 'status-bom';
        case 'Regular':
            return 'status-regular';
        case 'Insatisfatório':
            return 'status-insat';
        default:
            return 'status-empty';
    }
};

onMounted(async () => {
    await Promise.all([loadDashboard(), loadHeatmap()]);
});
</script>

<style scoped>
.grid-cols-13 {
    grid-template-columns: repeat(13, minmax(0, 1fr));
}
</style>
