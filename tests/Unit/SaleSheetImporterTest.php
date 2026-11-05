<?php

namespace Tests\Unit;

use App\Services\SaleSheetImporter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleSheetImporterTest extends TestCase
{
    #[Test]
    public function it_parses_travel_report_xlsx_with_payable_received_profit(): void
    {
        $path = $this->makeTravelReportXlsx();

        try {
            $rows = app(SaleSheetImporter::class)->parse($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(3, $rows);

        $this->assertSame('2026-10-03', $rows[0]['sale_date']);
        $this->assertSame('SHANAWAR JAVAID', $rows[0]['passenger_name']);
        $this->assertSame('BGY-DXB-ISB', $rows[0]['sector']);
        $this->assertSame('EK', $rows[0]['airline']);
        $this->assertSame(165053.0, $rows[0]['payable']);
        $this->assertSame(165053.0, $rows[0]['received']);
        $this->assertSame('FAMILY TKT', $rows[0]['via']);
        $this->assertSame(0.0, $rows[0]['profit']);

        $this->assertSame('RASHIDA AYUB*5', $rows[1]['passenger_name']);
        $this->assertSame(922465.0, $rows[1]['payable']);
        $this->assertSame(940000.0, $rows[1]['received']);
        $this->assertSame(17535.0, $rows[1]['profit']);

        $this->assertSame('2026-10-12', $rows[2]['sale_date']);
        $this->assertSame(19644.0, $rows[2]['payable']);
        $this->assertSame(21000.0, $rows[2]['received']);
        $this->assertSame(1356.0, $rows[2]['profit']);
    }

    #[Test]
    public function it_parses_real_october_2026_travel_report_xlsx(): void
    {
        $path = base_path('tests/Fixtures/OCT_26_TRAVEL_REPORT_DIG.xlsx');

        if (! is_file($path)) {
            $this->markTestSkipped('Travel report fixture not available.');
        }

        $rows = app(SaleSheetImporter::class)->parse($path);

        $this->assertCount(29, $rows);
        $this->assertSame('SHANAWAR JAVAID', $rows[0]['passenger_name']);
        $this->assertSame('FAMILY TKT', $rows[0]['via']);
        $this->assertSame(165053.0, $rows[0]['payable']);
        $this->assertSame(165053.0, $rows[0]['received']);
        $this->assertSame(0.0, $rows[0]['profit']);

        $names = array_column($rows, 'passenger_name');
        $this->assertContains('AZAD ALI KORAI', $names);
        $this->assertContains('REFUND PROFIT', $names);
        $this->assertContains('SHAMLA SANAM *4', $names);

        $refund = collect($rows)->firstWhere('passenger_name', 'REFUND PROFIT');
        $this->assertNotNull($refund);
        $this->assertSame(0.0, $refund['payable']);
        $this->assertSame(20000.0, $refund['received']);
        $this->assertSame(20000.0, $refund['profit']);

        $payable = array_sum(array_column($rows, 'payable'));
        $received = array_sum(array_column($rows, 'received'));
        $profit = array_sum(array_column($rows, 'profit'));

        $this->assertEqualsWithDelta(6607807.0, $payable, 0.01);
        $this->assertEqualsWithDelta(6740426.0, $received, 0.01);
        $this->assertEqualsWithDelta(132619.0, $profit, 0.01);
    }

    #[Test]
    public function it_carries_forward_blank_dates_from_previous_row(): void
    {
        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();

        $ws->fromArray([
            ['DATE', 'PASSENGER NAME', 'SECTOR', 'AIRLINE', 'PAYABLE', 'RECEIVED', 'VIA'],
            ['6-Oct-26', 'ZUNAIR JAVED', 'ISB-DXB-OTP', 'PK', '149,136', '149,136', 'SELF TKT'],
            [null, 'AZAD ALI KORAI', 'ISB-SKZ', 'PK', '23,587', '23,587', 'KORAI SHB TKT'],
            ['30-Oct-26', 'REFUND PROFIT', 'LHE-DOH-LCA', 'QR', '0', '20,000', 'REFUND PROFIT'],
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sale_carry_'.uniqid().'.xlsx';
        (new Xlsx($sheet))->save($path);

        try {
            $rows = app(SaleSheetImporter::class)->parse($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(3, $rows);
        $this->assertSame('2026-10-06', $rows[0]['sale_date']);
        $this->assertSame('AZAD ALI KORAI', $rows[1]['passenger_name']);
        $this->assertSame('2026-10-06', $rows[1]['sale_date']);
        $this->assertSame('REFUND PROFIT', $rows[2]['passenger_name']);
        $this->assertSame(20000.0, $rows[2]['profit']);
    }

    #[Test]
    public function it_parses_travel_report_csv(): void
    {
        $csv = <<<'CSV'
DATE,PASSENGER NAME,SECTOR,AIRLINE,PAYABLE,RECEIVED,VIA
3-Oct-26,SHANAWAR JAVAID,BGY-DXB-ISB,EK,"165,053","165,053",FAMILY TKT
12-Oct-26,RASHIDA AYUB*5,ISB-MAN,PK,"922,465","940,000",ZULFI TKT
TOTAL,,,,"6,607,807","6,740,426",
BALANCE,,,,,,-132619
CSV;

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sale_csv_'.uniqid().'.csv';
        file_put_contents($path, $csv);

        try {
            $rows = app(SaleSheetImporter::class)->parse($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(2, $rows);
        $this->assertSame(165053.0, $rows[0]['payable']);
        $this->assertSame(165053.0, $rows[0]['received']);
        $this->assertSame(0.0, $rows[0]['profit']);
        $this->assertSame(17535.0, $rows[1]['profit']);
        $this->assertSame('ZULFI TKT', $rows[1]['via']);
    }

    #[Test]
    public function it_still_accepts_legacy_register_mapping_payable_and_received(): void
    {
        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();

        $ws->fromArray([
            [
                'S#', 'Date', 'Passenger Name', 'PNR', 'Airline', 'Sector',
                'Fare / Person (PKR)', 'Pax', 'Total Fare / Sales (PKR)',
                'Profit (PKR)', 'Loss (PKR)', 'Net Profit/Loss (PKR)', 'Grand Total (PKR)',
            ],
            [1, '3/10/2026', 'SHANAWAR JAVAID', 'PENDING', 'EMIRATES (EK)', 'BGY-DXB-ISB', 165053.00, 1, 165053.00, 1000.00, 0, 1000.00, 166053.00],
            [2, '3/10/2026', 'MRS TUBASEM AYUB (ADT)', 'ABC123', 'PIA', 'ISB-MAN', 19644.00, 1, 19644.00, 1356.00, 0, 1356.00, 21000.00],
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sale_legacy_'.uniqid().'.xlsx';
        (new Xlsx($sheet))->save($path);

        try {
            $rows = app(SaleSheetImporter::class)->parse($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(2, $rows);
        $this->assertSame(165053.0, $rows[0]['payable']);
        $this->assertSame(166053.0, $rows[0]['received']);
        $this->assertSame(1000.0, $rows[0]['profit']);
        $this->assertSame(1356.0, $rows[1]['profit']);
        $this->assertSame('ABC123', $rows[1]['via']);
    }

    private function makeTravelReportXlsx(): string
    {
        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();

        $ws->fromArray([
            ['DATE', 'PASSENGER NAME', 'SECTOR', 'AIRLINE', 'PAYABLE', 'RECEIVED', 'VIA'],
            [null, null, null, null, null, null, null],
            ['3-Oct-26', 'SHANAWAR JAVAID', 'BGY-DXB-ISB', 'EK', '165,053', '165,053', 'FAMILY TKT'],
            ['12-Oct-26', 'RASHIDA AYUB*5', 'ISB-MAN', 'PK', '922,465', '940,000', 'ZULFI TKT'],
            ['12-Oct-26', 'SAIF ULLAH', 'KDU-ISB', 'PK', '19,644', '21,000', 'ZULFI TKT'],
            ['TOTAL', null, null, null, '1,107,162', '1,126,053', null],
            ['BALANCE', null, null, null, null, null, '-18,891'],
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sale_travel_'.uniqid().'.xlsx';
        (new Xlsx($sheet))->save($path);

        return $path;
    }
}
