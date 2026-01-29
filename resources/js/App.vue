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
                <h1 class="text-sm font-semibold leading-tight">Painel de Avaliação de Fornecedores</h1>
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
                        <h2 class="text-sm font-semibold">Importação Inteligente</h2>
                        <p class="text-xs text-muted">Arraste e solte os arquivos RIR para iniciar o processo de avaliação.</p>
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
                                Selecionar arquivos
                            </span>
                        </label>
                        <Button
                            class="rounded-lg bg-primary text-white px-4 py-2 text-xs font-medium hover:bg-primary/90"
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
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <h2 class="text-base font-semibold">Análise Consolidada Mensal</h2>
                    <div class="flex items-center gap-2 rounded-full border border-border bg-surface px-4 py-1.5 shadow-sm">
                        <span class="text-[11px] text-muted font-medium">Filtro:</span>
                        <Dropdown
                            v-model="selectedMonth"
                            :options="monthOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Selecionar mês"
                            appendTo="body"
                            scrollHeight="240px"
                            @change="loadDashboard"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
                        <div class="max-h-[420px] overflow-auto">
                            <DataTable :value="dashboard" stripedRows :key="`${selectedMonth}-${dashboard.length}`">
                            <Column field="fornecedor" header="FORNECEDOR"></Column>
                            <Column field="otimo" header="ÓTIMO"></Column>
                            <Column field="bom" header="BOM"></Column>
                            <Column field="regular" header="REGULAR"></Column>
                            <Column field="insatisfatorio" header="INSATISFATÓRIO"></Column>
                            </DataTable>
                        </div>
                    </div>

                    <div class="flex flex-col gap-5 p-6 rounded-xl border border-border bg-surface shadow-sm">
                        <div class="flex justify-between items-center">
                            <h3 class="text-[10px] font-extrabold uppercase tracking-wide text-muted">Distribuição por fornecedor</h3>
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

            <section class="flex justify-end gap-3">
                <Button
                    class="flex items-center gap-2 px-5 py-2 bg-primary text-white text-xs font-medium rounded-lg hover:bg-primary/90 whitespace-nowrap min-w-[150px]"
                    icon="pi pi-download"
                    :disabled="isExporting"
                    @click="exportarAvaliacao"
                >
                    <span v-if="!isExporting">Exportar Excel</span>
                    <span v-else class="inline-flex items-center gap-2">
                        <ProgressSpinner style="width: 14px; height: 14px" strokeWidth="6" />
                        Exportando
                    </span>
                </Button>
                <Button
                    class="flex items-center gap-2 px-5 py-2 text-xs font-medium rounded-lg whitespace-nowrap min-w-[150px]"
                    severity="danger"
                    icon="pi pi-trash"
                    @click="confirmarLimpeza"
                >
                    Limpar dados
                </Button>
            </section>
        </main>

        <footer class="border-t border-border bg-surface">
            <div class="mx-auto flex max-w-5xl flex-col sm:flex-row items-center justify-between gap-3 px-6 lg:px-10 py-6">
                <p class="text-xs text-muted">© 2026 Quality Management Systems — Ferramenta de Auditoria Interna</p>
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
const isExporting = ref(false);
const dashboard = ref([]);
const heatmap = ref([]);
const currentYear = new Date().getFullYear();
const selectedYear = ref(currentYear);
const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const mesesLabel = {
    Jan: 'Jan',
    Fev: 'Fev',
    Mar: 'Mar',
    Abr: 'Abr',
    Mai: 'Mai',
    Jun: 'Jun',
    Jul: 'Jul',
    Ago: 'Ago',
    Set: 'Set',
    Out: 'Out',
    Nov: 'Nov',
    Dez: 'Dez',
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
        if (Array.isArray(data.meses) && data.meses.length > 0) {
            const ordenados = [...data.meses].sort();
            selectedMonth.value = ordenados[ordenados.length - 1];
        }
        await loadDashboard();
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
    const params = {
        ...(selectedMonth.value ? { mes: selectedMonth.value } : {}),
        _ts: Date.now(),
    };
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

const exportarAvaliacao = async () => {
    try {
        isExporting.value = true;
        // Extrai o ano do mês selecionado (formato: "2025-12" -> "2025")
        const anoExportacao = selectedMonth.value ? selectedMonth.value.split('-')[0] : selectedYear.value;
        toast.add({
            severity: 'info',
            summary: 'Exportação em andamento',
            detail: 'Gerando o arquivo Excel, aguarde...',
            life: 3000,
        });
        const response = await axios.get('/api/exportar-avaliacao', {
            params: { ano: anoExportacao },
            responseType: 'blob',
        });
        const blob = new Blob([response.data], { type: response.headers['content-type'] });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `AVALIACAO_FORNECEDORES_${anoExportacao}.xlsx`;
        link.click();
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Falha na exportação',
            detail: 'Não foi possível gerar o arquivo XLSX.',
            life: 5000,
        });
    } finally {
        isExporting.value = false;
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
                monthOptions.value = [];
                selectedMonth.value = '';
                await loadDashboard();
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
    await loadDashboard();
});
</script>

<style scoped>
.grid-cols-13 {
    grid-template-columns: repeat(13, minmax(0, 1fr));
}
</style>
