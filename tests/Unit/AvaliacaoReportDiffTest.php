<?php

namespace Tests\Unit;

use App\Services\AvaliacaoReportDiff;
use PHPUnit\Framework\TestCase;

class AvaliacaoReportDiffTest extends TestCase
{
    public function test_diff_identifica_missing_extra_e_contagens(): void
    {
        $manual = [
            '01' => [
                'ACME' => ['otimo' => 1, 'bom' => 0, 'regular' => 0],
                'BETA' => ['otimo' => 0, 'bom' => 2, 'regular' => 0],
            ],
        ];

        $gerado = [
            '01' => [
                'BETA' => ['otimo' => 0, 'bom' => 3, 'regular' => 0],
                'GAMMA' => ['otimo' => 1, 'bom' => 0, 'regular' => 0],
            ],
        ];

        $service = new AvaliacaoReportDiff();
        $diff = $service->diff($manual, $gerado);

        $this->assertSame(1, $diff['01']['totais']['missing']);
        $this->assertSame(1, $diff['01']['totais']['extra']);
        $this->assertSame(1, $diff['01']['totais']['count_diffs']);
        $this->assertArrayHasKey('ACME', $diff['01']['missing']);
        $this->assertArrayHasKey('GAMMA', $diff['01']['extra']);
        $this->assertArrayHasKey('BETA', $diff['01']['count_diffs']);
    }
}
