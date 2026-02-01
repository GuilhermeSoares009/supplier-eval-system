<?php

namespace Tests\Unit;

use App\Services\AvaliacaoManualParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class AvaliacaoManualParserTest extends TestCase
{
    public function test_parse_spreadsheet_com_duas_colunas_de_mes(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'JANEIRO');
        $sheet->setCellValue('F1', 'FEVEREIRO');

        $sheet->setCellValue('A2', 'ACME');
        $sheet->setCellValue('B2', 1);
        $sheet->setCellValue('C2', 2);
        $sheet->setCellValue('D2', 0);

        $sheet->setCellValue('F2', 'BETA');
        $sheet->setCellValue('G2', 3);
        $sheet->setCellValue('H2', 0);
        $sheet->setCellValue('I2', 1);

        $tmp = tempnam(sys_get_temp_dir(), 'avaliacao_');
        $path = $tmp . '.xlsx';
        rename($tmp, $path);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        $parser = new AvaliacaoManualParser();
        $result = $parser->parse($path);

        unlink($path);

        $this->assertArrayHasKey('01', $result['data']);
        $this->assertArrayHasKey('02', $result['data']);
        $this->assertSame('ACME', $result['data']['01'][0]['fornecedor']);
        $this->assertSame(1, $result['data']['01'][0]['otimo']);
        $this->assertSame(2, $result['data']['01'][0]['bom']);
        $this->assertSame(0, $result['data']['01'][0]['regular']);
        $this->assertSame('BETA', $result['data']['02'][0]['fornecedor']);
        $this->assertSame(3, $result['data']['02'][0]['otimo']);
        $this->assertSame(0, $result['data']['02'][0]['bom']);
        $this->assertSame(1, $result['data']['02'][0]['regular']);
    }
}
