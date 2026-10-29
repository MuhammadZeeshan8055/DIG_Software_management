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
    public function it_parses_dhothar_monthly_air_ticketing_register_xlsx(): void
    {
        $path = $this->makeDhotharXlsx();

        try {
            $rows = app(SaleSheetImporter::class)->parse($path);
        } finally {
            @unlink($path);
        }

        $this->assertCount(3, $rows);

        $this->assertSame('2026-10-03', $rows[0]['sale_date']);
        $this->assertSame('SHANAWAR JAVAID', $rows[0]['passenger_name']);
        $this->assertSame('PENDING', $rows[0]['via']);
        $this->assertSame('EMIRATES (EK)', $rows[0]['airline']);
        $this->assertSame('BGY-DXB-ISB', $rows[0]['sector']);
        $this->assertSame(165053.0, $rows[0]['payable']);
        $this->assertSame(166053.0, $rows[0]['received']);
        $this->assertSame(1000.0, $rows[0]['gross_profit']);
        $this->assertSame(0.0, $rows[0]['loss']);
        $this->assertSame(1000.0, $rows[0]['profit']);

        $this->assertSame('MRS TUBASEM AYUB (ADT)', $rows[1]['passenger_name']);
        $this->assertSame(19644.0, $rows[1]['payable']);
        $this->assertSame(21000.0, $rows[1]['received']);
        $this->assertSame(1356.0, $rows[1]['gross_profit']);
        $this->assertSame(1356.0, $rows[1]['profit']);

        $this->assertSame('2026-10-20', $rows[2]['sale_date']);
        $this->assertSame(0.0, $rows[2]['profit']);
        $this->assertSame(25819.8, $rows[2]['received']);
    }

    #[Test]
    public function it_parses_real_dhothar_october_2026_xls_fixture(): void
    {
        $path = base_path('tests/Fixtures/Dhothar_Ticketing_October_2026.xls');
        $this->assertFileExists($path);

        $rows = app(SaleSheetImporter::class)->parse($path);

        $this->assertCount(56, $rows);
        $this->assertSame('SHANAWAR JAVAID', $rows[0]['passenger_name']);
        $this->assertSame('PENDING', $rows[0]['via']);
        $this->assertSame(165053.0, $rows[0]['payable']);
        $this->assertSame(165053.0, $rows[0]['received']);
        $this->assertSame(0.0, $rows[0]['profit']);

        $payable = array_sum(array_column($rows, 'payable'));
        $received = array_sum(array_column($rows, 'received'));
        $profit = array_sum(array_column($rows, 'profit'));

        $gross = array_sum(array_column($rows, 'gross_profit'));
        $loss = array_sum(array_column($rows, 'loss'));

        $this->assertEqualsWithDelta(6199883.32, $payable, 0.01);
        $this->assertEqualsWithDelta(6300915.02, $received, 0.01);
        $this->assertEqualsWithDelta(101031.7, $profit, 0.01);
        $this->assertEqualsWithDelta(101031.7, $gross, 0.01);
        $this->assertEqualsWithDelta(0.0, $loss, 0.01);
    }

    #[Test]
    public function it_parses_dhothar_register_csv_with_forced_comma_delimiter(): void
    {
        $csv = <<<'CSV'
Dhothar International Group Travel & Tours (Pvt. Ltd.)
Office 9 , First Floor , Zahoor Elahi Road , Gulberg III , Lahore
Monthly Air Ticketing Register — October 2026
S#,Date,Passenger Name,PNR,Airline,Sector,Fare / Person (PKR),Pax,Total Fare / Sales (PKR),Profit (PKR),Loss (PKR),Net Profit/Loss (PKR),Grand Total (PKR)
1,3/10/2026,SHANAWAR JAVAID,PENDING,EMIRATES (EK),BGY-DXB-ISB,"165,053.00",1,"165,053.00","1,000.00",0,"1,000.00","166,053.00"
2,3/10/2026,MRS TUBASEM AYUB (ADT),ABC123,PIA,ISB-MAN,"19,644.00",1,"19,644.00","1,356.00",0,"1,356.00","21,000.00"
,,,,OCTOBER TOTAL,,,,,,"6,199,883.32","101,031.70",0,"101,031.70","6,300,915.02"
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
        $this->assertSame(166053.0, $rows[0]['received']);
        $this->assertSame(1000.0, $rows[0]['gross_profit']);
        $this->assertSame(0.0, $rows[0]['loss']);
        $this->assertSame(1000.0, $rows[0]['profit']);
        $this->assertSame('ABC123', $rows[1]['via']);
    }

    private function makeDhotharXlsx(): string
    {
        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();

        $ws->fromArray([
            ['Dhothar International Group Travel & Tours (Pvt. Ltd.)'],
            ['Office 9 , First Floor , Zahoor Elahi Road , Gulberg III , Lahore'],
            ['Monthly Air Ticketing Register — October 2026'],
            [
                'S#', 'Date', 'Passenger Name', 'PNR', 'Airline', 'Sector',
                'Fare / Person (PKR)', 'Pax', 'Total Fare / Sales (PKR)',
                'Profit (PKR)', 'Loss (PKR)', 'Net Profit/Loss (PKR)', 'Grand Total (PKR)',
            ],
            [1, '3/10/2026', 'SHANAWAR JAVAID', 'PENDING', 'EMIRATES (EK)', 'BGY-DXB-ISB', 165053.00, 1, 165053.00, 1000.00, 0, 1000.00, 166053.00],
            [2, '3/10/2026', 'MRS TUBASEM AYUB (ADT)', 'ABC123', 'PIA', 'ISB-MAN', 19644.00, 1, 19644.00, 1356.00, 0, 1356.00, 21000.00],
            [3, '20/10/2026', 'MR HAROON ARSHAD (ADT)', '9HATE8', 'PIA', 'ISB-KHI', 25819.80, 1, 25819.80, 0, 0, 0, 25819.80],
            [null, null, 'OCTOBER TOTAL', null, null, null, null, 56, 6199883.32, 101031.70, 0, 101031.70, 6300915.02],
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sale_xlsx_'.uniqid().'.xlsx';
        (new Xlsx($sheet))->save($path);

        return $path;
    }
}
