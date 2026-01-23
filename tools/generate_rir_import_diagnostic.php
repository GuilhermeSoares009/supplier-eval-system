<?php

declare(strict_types=1);

$logsDir = __DIR__ . '/../storage/logs';
$outPath = __DIR__ . '/../RIR_IMPORT_DIAGNOSTIC.txt';

$files = glob($logsDir . '/rir_import-*.log') ?: [];
if (count($files) === 0) {
    fwrite(STDERR, "Nenhum arquivo de log encontrado em {$logsDir} (rir_import-*.log)\n");
    exit(1);
}

sort($files);
$latest = end($files);

$maxLines = 500;
$contents = file_get_contents($latest);
if ($contents === false) {
    fwrite(STDERR, "Falha ao ler log: {$latest}\n");
    exit(1);
}

$lines = preg_split('/\R/', $contents) ?: [];
$tail = array_slice($lines, max(0, count($lines) - $maxLines));

$interestingPatterns = [
    'Falha ao mapear',
    'Colunas obrigat',
    'Cabeçalho n',
    'Linha de cabeçalho detectada',
    'Mapa de colunas detectado',
    'Falha validável na importação RIR',
    'Erro inesperado na importação RIR',
    'import_id',
];

$interesting = [];
foreach ($lines as $line) {
    foreach ($interestingPatterns as $pattern) {
        if (stripos($line, $pattern) !== false) {
            $interesting[] = $line;
            break;
        }
    }
}

$report = [];
$report[] = 'DIAGNÓSTICO - IMPORTAÇÃO RIR';
$report[] = 'Gerado em: ' . date('Y-m-d H:i:s');
$report[] = 'Log analisado: ' . $latest;
$report[] = 'Total linhas no log: ' . count($lines);
$report[] = '';

$report[] = '--- LINHAS RELEVANTES (filtradas) ---';
if (count($interesting) === 0) {
    $report[] = '(nenhuma linha relevante encontrada)';
} else {
    foreach ($interesting as $line) {
        $report[] = $line;
    }
}

$report[] = '';
$report[] = '--- ÚLTIMAS ' . $maxLines . ' LINHAS ---';
foreach ($tail as $line) {
    $report[] = $line;
}

file_put_contents($outPath, implode(PHP_EOL, $report) . PHP_EOL);

fwrite(STDOUT, "Arquivo gerado: {$outPath}\n");
