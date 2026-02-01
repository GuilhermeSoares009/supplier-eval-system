<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Services\AvaliacaoManualParser;
use App\Services\AvaliacaoReportDiff;

$options = getopt('', ['manual:', 'gerado:', 'out::', 'snapshot::']);

$manualPath = $options['manual'] ?? null;
$geradoPath = $options['gerado'] ?? null;
$outPath = $options['out'] ?? null;
$snapshotPath = $options['snapshot'] ?? null;

if (!$manualPath || !$geradoPath) {
    fwrite(STDERR, "Uso: php tools/avaliacao_diff.php --manual=CAMINHO.ods --gerado=CAMINHO.xlsx [--out=diff.json] [--snapshot=manual.json]\n");
    exit(1);
}

if (!is_file($manualPath)) {
    fwrite(STDERR, "Arquivo manual nÃ£o encontrado: {$manualPath}\n");
    exit(1);
}

if (!is_file($geradoPath)) {
    fwrite(STDERR, "Arquivo gerado nÃ£o encontrado: {$geradoPath}\n");
    exit(1);
}

$parser = new AvaliacaoManualParser();
$manual = $parser->parse($manualPath);
$gerado = $parser->parse($geradoPath);

$diffService = new AvaliacaoReportDiff();
$diff = $diffService->diff($manual['index'], $gerado['index']);

foreach ($diff as $mes => $dados) {
    $totais = $dados['totais'];
    fwrite(
        STDOUT,
        sprintf(
            "%s: manual %d | gerado %d | missing %d | extra %d | count diffs %d\n",
            $mes,
            $totais['manual'],
            $totais['gerado'],
            $totais['missing'],
            $totais['extra'],
            $totais['count_diffs']
        )
    );
}

if ($outPath) {
    file_put_contents($outPath, json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fwrite(STDOUT, "Diff salvo em {$outPath}\n");
}

if ($snapshotPath) {
    $snapshot = [
        'data' => $manual['data'],
        'index' => $manual['index'],
    ];
    file_put_contents($snapshotPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fwrite(STDOUT, "Snapshot manual salvo em {$snapshotPath}\n");
}
