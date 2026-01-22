<template>
    <div class="min-h-screen bg-background-light">
        <Toast />
        <ConfirmDialog />

        <header class="flex items-center justify-between border-b border-[#dbdfe6] bg-white px-10 py-3 sticky top-0 z-40">
            <div class="flex items-center gap-4 text-[#111318]">
                <div class="size-6 text-primary">
                    <svg fill="currentColor" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-bold leading-tight tracking-[-0.015em]">Supplier Evaluation Dashboard</h2>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    class="h-10 rounded-lg border border-error/30 text-error bg-white px-3 text-sm font-semibold hover:bg-error/5"
                    icon="pi pi-trash"
                    @click="confirmarLimpeza"
                >
                    Limpar dados
                </Button>
                <Button class="h-10 w-10 rounded-lg bg-[#f0f2f4] text-[#111318]" icon="pi pi-bell" />
                <Button class="h-10 w-10 rounded-lg bg-[#f0f2f4] text-[#111318]" icon="pi pi-user" />
            </div>
        </header>

        <main class="px-4 lg:px-40 py-8 space-y-8">
            <section>
                <div
                    class="flex flex-col gap-4 rounded-xl border-2 border-dashed border-[#dbdfe6] bg-[#f0f2f4]/50 p-8 text-center items-center justify-center"
                    :class="{ 'border-primary bg-primary/5': isDragging }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                >
                    <div class="flex flex-col gap-2">
                        <h3 class="text-xl font-bold">Importação Inteligente</h3>
                        <p class="text-slate-500">Arraste e solte os arquivos RIR para iniciar o processo de avaliação.</p>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        <div
                            v-for="file in files"
                            :key="file.name"
                            class="flex h-10 items-center justify-center gap-x-2 rounded-lg bg-primary/10 border border-primary/20 px-4"
                        >
                            <span class="pi pi-check-circle text-primary text-sm"></span>
                            <p class="text-primary text-sm font-semibold">{{ file.name }}</p>
                            <span class="pi pi-times text-primary text-sm cursor-pointer" @click="removeFile(file)"></span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <label class="inline-flex">
                            <input ref="fileInput" type="file" multiple class="hidden" @change="handleFileInput" />
                            <span class="cursor-pointer rounded-lg bg-white px-4 py-2 text-sm font-semibold border border-[#dbdfe6] hover:bg-[#f0f2f4]">
                                Selecionar arquivos
                            </span>
                        </label>
                        <Button
                            class="rounded-lg bg-primary text-white px-4 py-2 text-sm font-bold hover:bg-primary/90"
                            :disabled="files.length === 0 || isUploading"
                            @click="uploadFiles"
                        >
                            <span v-if="!isUploading">Importar</span>
                            <span v-else class="inline-flex items-center gap-2">
                                <ProgressSpinner style="width: 16px; height: 16px" strokeWidth="6" />
                                Importando
                            </span>
                        </Button>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between px-4 flex-wrap gap-3">
                    <h2 class="text-[22px] font-bold leading-tight tracking-[-0.015em]">Análise Consolidada Mensal</h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-medium">Filtro:</span>
                        <Dropdown
                            v-model="selectedMonth"
                            :options="monthOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="min-w-[180px]"
                            @change="loadDashboard"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="overflow-hidden rounded-xl border border-[#dbdfe6] bg-white shadow-sm">
                        <div class="max-h-[420px] overflow-y-auto">
                            <DataTable :value="dashboard" stripedRows class="text-sm">
                            <Column field="fornecedor" header="Fornecedor"></Column>
                            <Column field="otimo" header="Ótimo"></Column>
                            <Column field="bom" header="Bom"></Column>
                            <Column field="regular" header="Regular"></Column>
                            <Column field="insatisfatorio" header="Insatisfatório"></Column>
                            <Column field="total" header="Total"></Column>
                            </DataTable>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6 p-6 rounded-xl border border-[#dbdfe6] bg-white shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-bold text-sm uppercase text-slate-500">Distribuição por fornecedor</h3>
                            <div class="flex gap-4 text-[10px] font-bold uppercase">
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-tertiary rounded-sm"></i> Ótimo</span>
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-primary rounded-sm"></i> Bom</span>
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-secondary rounded-sm"></i> Regular</span>
                                <span class="flex items-center gap-1"><i class="w-3 h-3 bg-error rounded-sm"></i> Insat</span>
                            </div>
                        </div>

                        <div class="space-y-4 max-h-[420px] overflow-y-auto pr-2">
                            <div v-for="linha in dashboard" :key="linha.fornecedor" class="space-y-2">
                            <div class="flex justify-between text-xs font-medium">
                                <span>{{ linha.fornecedor }}</span>
                                <span>{{ linha.total }} total</span>
                            </div>
                            <div class="w-full h-8 flex rounded overflow-hidden bg-[#f0f2f4]">
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
                    <h2 class="text-[22px] font-bold leading-tight tracking-[-0.015em]">Heatmap de Performance Anual</h2>
                    <Button
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90"
                        icon="pi pi-download"
                        @click="exportarAvaliacao"
                    >
                        Exportar Avaliação Consolidada
                    </Button>
                </div>
                <div class="p-6 rounded-xl border border-[#dbdfe6] bg-white shadow-sm overflow-x-auto">
                    <div class="min-w-[800px]">
                        <div class="grid grid-cols-13 gap-1 mb-4 text-xs font-bold text-slate-500">
                            <div>Fornecedor</div>
                            <div v-for="mes in meses" :key="mes" class="text-center">{{ mes }}</div>
                        </div>

                        <div v-for="linha in heatmap" :key="linha.fornecedor" class="grid grid-cols-13 gap-1 items-center mb-2">
                            <div class="text-sm font-medium">{{ linha.fornecedor }}</div>
                            <div
                                v-for="mes in meses"
                                :key="mes"
                                class="h-8 rounded"
                                :class="statusClass(linha.meses[mes])"
                                :title="linha.meses[mes] || 'Sem dados'"
                            ></div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-6 text-[11px] text-slate-500 font-bold uppercase tracking-widest">
                        <div class="flex items-center gap-2"><i class="w-3 h-3 bg-tertiary rounded-sm"></i> Ótimo</div>
                        <div class="flex items-center gap-2"><i class="w-3 h-3 bg-primary rounded-sm"></i> Bom</div>
                        <div class="flex items-center gap-2"><i class="w-3 h-3 bg-secondary rounded-sm"></i> Regular</div>
                        <div class="flex items-center gap-2"><i class="w-3 h-3 bg-error rounded-sm"></i> Insatisfatório</div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-white border-t border-[#dbdfe6] px-10 py-6 text-center text-sm text-slate-500">
            <p>© 2026 Quality Management Systems - Internal Supplier Audit Tool</p>
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
            const label = meses[indice] ? `${meses[indice]} ${ano}` : mes;
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
        acceptClass: 'bg-error text-white px-4 py-2 rounded-lg',
        rejectClass: 'bg-white text-slate-600 px-4 py-2 rounded-lg border border-[#dbdfe6]',
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
            return 'bg-tertiary';
        case 'Bom':
            return 'bg-primary';
        case 'Regular':
            return 'bg-secondary';
        case 'Insatisfatório':
            return 'bg-error';
        default:
            return 'bg-[#e5e7eb]';
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
