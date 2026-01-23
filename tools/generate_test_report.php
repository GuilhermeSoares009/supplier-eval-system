<?php

declare(strict_types=1);

$xmlPath = __DIR__ . '/../TEST_REPORT.xml';
$outPath = __DIR__ . '/../TEST_REPORT.txt';

if (!file_exists($xmlPath)) {
    fwrite(STDERR, "Relatório XML não encontrado em {$xmlPath}\n");
    exit(1);
}

$xml = new SimpleXMLElement(file_get_contents($xmlPath));

$tests = 0;
$assertions = 0;
$failures = 0;
$errors = 0;
$time = 0.0;
$timestamp = (string) ($xml['timestamp'] ?? date('Y-m-d H:i:s'));

if (isset($xml->testsuite)) {
    foreach ($xml->testsuite as $suite) {
        $tests += (int) ($suite['tests'] ?? 0);
        $assertions += (int) ($suite['assertions'] ?? 0);
        $failures += (int) ($suite['failures'] ?? 0);
        $errors += (int) ($suite['errors'] ?? 0);
        $time += (float) ($suite['time'] ?? 0);
        if (isset($suite['timestamp'])) {
            $timestamp = (string) $suite['timestamp'];
        }
    }
} else {
    $tests = (int) ($xml['tests'] ?? 0);
    $assertions = (int) ($xml['assertions'] ?? 0);
    $failures = (int) ($xml['failures'] ?? 0);
    $errors = (int) ($xml['errors'] ?? 0);
    $time = (float) ($xml['time'] ?? 0);
}

$status = ($failures + $errors) > 0 ? 'FALHA' : 'SUCESSO';

$lines = [];
$lines[] = 'RELATÓRIO DE TESTES';
$lines[] = 'Projeto: Automação Fornecedores';
$lines[] = 'Data: ' . date('d/m/Y');
$lines[] = 'Execução: ' . $timestamp;
$lines[] = 'Ferramenta: Pest (log JUnit)';
$lines[] = '';
$lines[] = 'Resumo';
$lines[] = '- Status: ' . $status;
$lines[] = '- Total de testes: ' . $tests;
$lines[] = '- Asserções: ' . $assertions;
$lines[] = '- Falhas: ' . $failures;
$lines[] = '- Erros: ' . $errors;
$lines[] = '- Tempo: ' . number_format($time, 2) . 's';
$lines[] = '';
$lines[] = 'Falhas/Erros (se houver)';

if (($failures + $errors) === 0) {
    $lines[] = '- Nenhuma falha registrada.';
} else {
    foreach ($xml->testsuite as $suite) {
        foreach ($suite->testcase as $testcase) {
            if (isset($testcase->failure)) {
                $lines[] = '- FAIL: ' . $testcase['classname'] . '::' . $testcase['name'];
            }
            if (isset($testcase->error)) {
                $lines[] = '- ERROR: ' . $testcase['classname'] . '::' . $testcase['name'];
            }
        }
    }
}

$lines[] = '';
$lines[] = 'Comando sugerido';
$lines[] = '- composer test:report';

file_put_contents($outPath, implode(PHP_EOL, $lines) . PHP_EOL);
