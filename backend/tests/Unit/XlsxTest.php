<?php

namespace Tests\Unit;

use App\Support\Xlsx\XlsxException;
use App\Support\Xlsx\XlsxReader;
use App\Support\Xlsx\XlsxWriter;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class XlsxTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function roundTrip(XlsxWriter $writer): XlsxReader
    {
        $this->files[] = $path = $writer->saveToTemp();

        return new XlsxReader($path);
    }

    public function test_values_survive_a_round_trip(): void
    {
        $reader = $this->roundTrip((new XlsxWriter)->addSheet('Data', [10, 20], [
            ['ID', 'Nama', 'Aktif', 'Rating'],
            [1, 'Kopi & <Teh> "Saluang"', true, 4.8],
            [2, '  spasi di tepi  ', false, null],
            [3, 'Émoji ☕ dan ÄÖÜ', null, 0],
        ]));

        $rows = $reader->rows();
        $reader->close();

        $this->assertSame([0 => 1, 1 => 'Kopi & <Teh> "Saluang"', 2 => true, 3 => 4.8], $rows[2]);
        $this->assertSame('  spasi di tepi  ', $rows[3][1]);
        $this->assertFalse($rows[3][2]);
        $this->assertArrayNotHasKey(3, $rows[3], 'A null cell stays empty.');
        $this->assertSame('Émoji ☕ dan ÄÖÜ', $rows[4][1]);
        $this->assertSame(0, $rows[4][3]);
    }

    public function test_text_that_looks_like_a_formula_stays_text(): void
    {
        $reader = $this->roundTrip((new XlsxWriter)->addSheet('S', [], [['=HYPERLINK("http://x","klik")']]));
        $this->assertSame('=HYPERLINK("http://x","klik")', $reader->rows()[1][0]);
        $reader->close();

        $xml = (new ZipArchive);
        $xml->open(end($this->files));
        $this->assertStringNotContainsString('<f>', $xml->getFromName('xl/worksheets/sheet1.xml'));
        $xml->close();
    }

    public function test_sheet_names_are_sanitised_and_unique(): void
    {
        $reader = $this->roundTrip((new XlsxWriter)
            ->addSheet('Data/2026: [final]?', [], [['a']])
            ->addSheet('Data/2026: [final]?', [], [['b']]));

        [$first, $second] = $reader->sheetNames();
        $reader->close();

        $this->assertDoesNotMatchRegularExpression('~[:\\\\/?*\[\]]~', $first);
        $this->assertNotSame($first, $second);
        $this->assertLessThanOrEqual(31, mb_strlen($second));
    }

    public function test_reads_shared_strings_and_rich_text_written_by_other_tools(): void
    {
        // Excel, LibreOffice and exceljs write text to a shared-strings table
        // instead of inline, and may split it into formatted runs.
        $this->files[] = $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Lembar1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="/xl/worksheets/data.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Nama</t></si><si><r><rPr><b/></rPr><t>Warung </t></r><r><t>Kenari</t></r></si></sst>');
        $zip->addFromString('xl/worksheets/data.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="B1" t="s"><v>0</v></c></row><row r="5"><c r="B5" t="s"><v>1</v></c><c r="C5" t="str"><f>1+1</f><v>2</v></c><c r="D5" t="e"><v>#N/A</v></c></row></sheetData></worksheet>');
        $zip->close();

        $reader = new XlsxReader($path);
        $rows = $reader->rows();
        $reader->close();

        $this->assertSame(['Lembar1'], (new XlsxReader($path))->sheetNames());
        $this->assertSame([1 => 'Nama'], $rows[1]);
        $this->assertSame([1 => 'Warung Kenari', 2 => '2'], $rows[5], 'Error cells are empty, formulas give their cached value.');
    }

    public function test_row_limit_is_enforced(): void
    {
        $reader = $this->roundTrip((new XlsxWriter)->addSheet('S', [], [['h'], ['1'], ['2'], ['3']]));

        $this->expectException(XlsxException::class);
        try {
            $reader->rows(0, 2);
        } finally {
            $reader->close();
        }
    }

    public function test_a_non_zip_file_is_refused(): void
    {
        $this->files[] = $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, 'bukan excel');

        $this->expectException(XlsxException::class);
        new XlsxReader($path);
    }

    public function test_column_letters(): void
    {
        $this->assertSame(['A', 'Z', 'AA', 'AZ', 'BA', 'ZZ', 'AAA'], array_map(
            [XlsxWriter::class, 'columnLetter'],
            [0, 25, 26, 51, 52, 701, 702],
        ));
        $this->assertSame(702, XlsxReader::columnIndex('AAA12'));
    }
}
