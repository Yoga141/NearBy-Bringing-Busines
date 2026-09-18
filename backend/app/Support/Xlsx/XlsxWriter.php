<?php

namespace App\Support\Xlsx;

use XMLWriter;
use ZipArchive;

/**
 * Minimal, dependency-free .xlsx (Office Open XML) writer.
 *
 * Why not PhpSpreadsheet: `vendor/` is committed and deployed with a plain
 * `cp -R` (see .cpanel.yml), so every package added there ships to the server.
 * The dashboard only needs flat tables of text and numbers, which takes a few
 * XML parts inside a zip - no reason to carry a 10 MB library for it.
 *
 * Supported: several worksheets, column widths, a styled + frozen header row,
 * strings, integers/floats and booleans. Strings are written as inline strings,
 * so a value such as "=SUM(A1)" is stored as text and never becomes a formula
 * (no spreadsheet-formula injection from user-entered data).
 */
class XlsxWriter
{
    private const STYLE_DEFAULT = 0;

    private const STYLE_HEADER = 1;

    private const STYLE_TITLE = 2;

    /** @var list<array{name: string, widths: list<float>, rows: list<list<mixed>>, header: bool, title: bool}> */
    private array $sheets = [];

    private string $creator = 'NearBy Balikpapan';

    public function setCreator(string $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Add a worksheet.
     *
     * @param  list<float|int>  $widths  Column widths in Excel "characters".
     * @param  list<list<mixed>>  $rows  Row-major cell values; null leaves a cell empty.
     * @param  bool  $headerRow  Style and freeze the first row as a table header.
     * @param  bool  $titleRow  Style the first row as a heading (used for guide sheets).
     */
    public function addSheet(string $name, array $widths, array $rows, bool $headerRow = true, bool $titleRow = false): static
    {
        $this->sheets[] = [
            'name' => $this->sheetName($name),
            'widths' => array_map('floatval', $widths),
            'rows' => $rows,
            'header' => $headerRow,
            'title' => $titleRow,
        ];

        return $this;
    }

    /** Write the workbook to `$path`. */
    public function save(string $path): void
    {
        if (! $this->sheets) {
            throw new XlsxException('Workbook harus punya minimal satu lembar kerja.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new XlsxException('Gagal membuat berkas Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('docProps/core.xml', $this->coreProps());
        $zip->addFromString('docProps/app.xml', $this->appProps());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', $this->worksheet($sheet));
        }

        if (! $zip->close()) {
            throw new XlsxException('Gagal menyimpan berkas Excel.');
        }
    }

    /** Write the workbook to a fresh temp file and return its path. */
    public function saveToTemp(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($path === false) {
            throw new XlsxException('Gagal membuat berkas sementara.');
        }
        $this->save($path);

        return $path;
    }

    /** 0-based column index to its letter name: 0 → A, 25 → Z, 26 → AA. */
    public static function columnLetter(int $index): string
    {
        $letter = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letter = chr(65 + ($n - 1) % 26).$letter;
        }

        return $letter;
    }

    /**
     * @param  array{name: string, widths: list<float>, rows: list<list<mixed>>, header: bool, title: bool}  $sheet
     */
    private function worksheet(array $sheet): string
    {
        $x = $this->xml();
        $x->startElement('worksheet');
        $x->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $x->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        if ($sheet['header'] && count($sheet['rows']) > 0) {
            // Freeze the header row so it stays visible while scrolling.
            $x->startElement('sheetViews');
            $x->startElement('sheetView');
            $x->writeAttribute('workbookViewId', '0');
            $x->startElement('pane');
            $x->writeAttribute('ySplit', '1');
            $x->writeAttribute('topLeftCell', 'A2');
            $x->writeAttribute('activePane', 'bottomLeft');
            $x->writeAttribute('state', 'frozen');
            $x->endElement();
            $x->endElement();
            $x->endElement();
        }

        if ($sheet['widths']) {
            $x->startElement('cols');
            foreach ($sheet['widths'] as $i => $width) {
                $x->startElement('col');
                $x->writeAttribute('min', (string) ($i + 1));
                $x->writeAttribute('max', (string) ($i + 1));
                $x->writeAttribute('width', (string) $width);
                $x->writeAttribute('customWidth', '1');
                $x->endElement();
            }
            $x->endElement();
        }

        $x->startElement('sheetData');
        foreach ($sheet['rows'] as $r => $cells) {
            $rowNumber = $r + 1;
            $style = match (true) {
                $r === 0 && $sheet['header'] => self::STYLE_HEADER,
                $r === 0 && $sheet['title'] => self::STYLE_TITLE,
                default => self::STYLE_DEFAULT,
            };

            $x->startElement('row');
            $x->writeAttribute('r', (string) $rowNumber);
            if ($style === self::STYLE_HEADER) {
                $x->writeAttribute('ht', '22');
                $x->writeAttribute('customHeight', '1');
            }

            foreach (array_values($cells) as $c => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $this->cell($x, self::columnLetter($c).$rowNumber, $value, $style);
            }
            $x->endElement();
        }
        $x->endElement(); // sheetData

        $x->endElement(); // worksheet

        return $x->outputMemory();
    }

    private function cell(XMLWriter $x, string $ref, mixed $value, int $style): void
    {
        $x->startElement('c');
        $x->writeAttribute('r', $ref);
        if ($style !== self::STYLE_DEFAULT) {
            $x->writeAttribute('s', (string) $style);
        }

        if (is_bool($value)) {
            $x->writeAttribute('t', 'b');
            $x->writeElement('v', $value ? '1' : '0');
        } elseif (is_int($value) || (is_float($value) && is_finite($value))) {
            $x->writeElement('v', (string) $value);
        } else {
            $x->writeAttribute('t', 'inlineStr');
            $x->startElement('is');
            $x->startElement('t');
            $text = $this->clean((string) $value);
            if ($text !== trim($text)) {
                $x->writeAttribute('xml:space', 'preserve');
            }
            $x->text($text);
            $x->endElement();
            $x->endElement();
        }

        $x->endElement();
    }

    private function workbook(): string
    {
        $x = $this->xml();
        $x->startElement('workbook');
        $x->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $x->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $x->startElement('sheets');
        foreach ($this->sheets as $i => $sheet) {
            $x->startElement('sheet');
            $x->writeAttribute('name', $sheet['name']);
            $x->writeAttribute('sheetId', (string) ($i + 1));
            $x->writeAttribute('r:id', 'rId'.($i + 1));
            $x->endElement();
        }
        $x->endElement();
        $x->endElement();

        return $x->outputMemory();
    }

    private function workbookRels(): string
    {
        $x = $this->xml();
        $x->startElement('Relationships');
        $x->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');
        foreach ($this->sheets as $i => $_) {
            $this->relationship($x, 'rId'.($i + 1), 'worksheet', 'worksheets/sheet'.($i + 1).'.xml');
        }
        $this->relationship($x, 'rId'.(count($this->sheets) + 1), 'styles', 'styles.xml');
        $x->endElement();

        return $x->outputMemory();
    }

    private function rootRels(): string
    {
        $x = $this->xml();
        $x->startElement('Relationships');
        $x->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $this->relationship($x, 'rId1', 'officeDocument', 'xl/workbook.xml');
        $x->startElement('Relationship');
        $x->writeAttribute('Id', 'rId2');
        $x->writeAttribute('Type', 'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties');
        $x->writeAttribute('Target', 'docProps/core.xml');
        $x->endElement();
        $this->relationship($x, 'rId3', 'extended-properties', 'docProps/app.xml');
        $x->endElement();

        return $x->outputMemory();
    }

    private function relationship(XMLWriter $x, string $id, string $type, string $target): void
    {
        $x->startElement('Relationship');
        $x->writeAttribute('Id', $id);
        $x->writeAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/'.$type);
        $x->writeAttribute('Target', $target);
        $x->endElement();
    }

    private function contentTypes(): string
    {
        $x = $this->xml();
        $x->startElement('Types');
        $x->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/content-types');

        foreach ([
            'rels' => 'application/vnd.openxmlformats-package.relationships+xml',
            'xml' => 'application/xml',
        ] as $ext => $type) {
            $x->startElement('Default');
            $x->writeAttribute('Extension', $ext);
            $x->writeAttribute('ContentType', $type);
            $x->endElement();
        }

        $overrides = [
            '/xl/workbook.xml' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml',
            '/xl/styles.xml' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml',
            '/docProps/core.xml' => 'application/vnd.openxmlformats-package.core-properties+xml',
            '/docProps/app.xml' => 'application/vnd.openxmlformats-officedocument.extended-properties+xml',
        ];
        foreach ($this->sheets as $i => $_) {
            $overrides['/xl/worksheets/sheet'.($i + 1).'.xml'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml';
        }
        foreach ($overrides as $part => $type) {
            $x->startElement('Override');
            $x->writeAttribute('PartName', $part);
            $x->writeAttribute('ContentType', $type);
            $x->endElement();
        }

        $x->endElement();

        return $x->outputMemory();
    }

    /**
     * Three cell formats: default, the table header (bold white on navy, the
     * dashboard's brand colour) and a bold heading for guide sheets.
     */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3">'
            .'<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="13"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF16324B"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function coreProps(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $x = $this->xml();
        $x->startElement('cp:coreProperties');
        $x->writeAttribute('xmlns:cp', 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties');
        $x->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $x->writeAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
        $x->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $x->writeElement('dc:creator', $this->clean($this->creator));
        foreach (['dcterms:created', 'dcterms:modified'] as $name) {
            $x->startElement($name);
            $x->writeAttribute('xsi:type', 'dcterms:W3CDTF');
            $x->text($now);
            $x->endElement();
        }
        $x->endElement();

        return $x->outputMemory();
    }

    private function appProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            .'<Application>NearBy Balikpapan</Application></Properties>';
    }

    private function xml(): XMLWriter
    {
        $x = new XMLWriter;
        $x->openMemory();
        $x->startDocument('1.0', 'UTF-8', 'yes');

        return $x;
    }

    /** Excel sheet names: max 31 chars, none of : \ / ? * [ ], unique. */
    private function sheetName(string $name): string
    {
        $name = trim(preg_replace('~[:\\\\/?*\[\]]~', ' ', $name) ?? '') ?: 'Sheet';
        $name = mb_substr($name, 0, 31);

        $taken = array_column($this->sheets, 'name');
        $base = $name;
        for ($n = 2; in_array($name, $taken, true); $n++) {
            $name = mb_substr($base, 0, 31 - strlen(" ($n)"))." ($n)";
        }

        return $name;
    }

    /** Strip characters XML 1.0 cannot carry and fix invalid UTF-8. */
    private function clean(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '';
    }
}
