<?php

namespace App\Support\Xlsx;

use XMLReader;
use ZipArchive;

/**
 * Minimal, dependency-free .xlsx reader - the counterpart of {@see XlsxWriter}.
 *
 * Reads the values (not formatting) of one worksheet, streaming the XML with
 * XMLReader so memory stays flat regardless of how the file was styled. Works
 * with files saved by Excel, LibreOffice, Google Sheets and exceljs: shared
 * strings, inline strings, rich text, cached formula results, booleans and
 * numbers are all understood.
 *
 * Defensive by design, since the file comes straight from an upload:
 *  - the zip is opened read-only and every part is size-checked before it is
 *    inflated (zip-bomb guard);
 *  - XML is parsed with network access disabled and without entity expansion;
 *  - reading stops with a clear error once `$maxRows` data rows are exceeded.
 */
class XlsxReader
{
    /** Largest single XML part we are willing to inflate (bytes). */
    private const MAX_PART_BYTES = 50 * 1024 * 1024;

    private ZipArchive $zip;

    /** @var list<string>|null */
    private ?array $sharedStrings = null;

    private bool $closed = false;

    public function __construct(string $path)
    {
        $zip = new ZipArchive;
        if (! is_file($path) || $zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new XlsxException('File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).');
        }
        if ($zip->locateName('xl/workbook.xml') === false) {
            $zip->close();
            throw new XlsxException('File ini bukan workbook Excel (.xlsx) yang valid.');
        }
        $this->zip = $zip;
    }

    /**
     * Release the file handle. Call it before deleting the file: Windows keeps
     * an open zip locked, so an unlink() would otherwise fail.
     */
    public function close(): void
    {
        if (! $this->closed) {
            $this->closed = true;
            $this->zip->close();
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Names of the worksheets, in workbook order.
     *
     * @return list<string>
     */
    public function sheetNames(): array
    {
        return array_column($this->sheets(), 'name');
    }

    /**
     * Rows of a worksheet (the first one by default).
     *
     * Each row is keyed by its 1-based sheet row number; each cell by its
     * 0-based column index. Empty cells are simply absent.
     *
     * @return array<int, array<int, string|int|float|bool>>
     */
    public function rows(int $sheetIndex = 0, int $maxRows = PHP_INT_MAX): array
    {
        $sheets = $this->sheets();
        if (! isset($sheets[$sheetIndex])) {
            throw new XlsxException('File Excel ini tidak punya lembar kerja.');
        }

        $reader = $this->open($sheets[$sheetIndex]['path']);
        $rows = [];
        $rowNumber = 0;
        $dataRows = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rowNumber = (int) ($reader->getAttribute('r') ?: $rowNumber + 1);
            $cells = $reader->isEmptyElement ? [] : $this->readRow($reader);
            if ($cells === []) {
                continue;
            }

            // Row 1 is the header; only data rows count toward the limit.
            if ($rowNumber > 1 && ++$dataRows > $maxRows) {
                $reader->close();
                throw new XlsxException("Terlalu banyak baris. Maksimal {$maxRows} baris data per impor - pecah file menjadi beberapa bagian.");
            }

            $rows[$rowNumber] = $cells;
        }
        $reader->close();

        return $rows;
    }

    /** "B" → 1, "AA" → 26: the 0-based index of a cell reference's column. */
    public static function columnIndex(string $ref): int
    {
        $letters = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $ref));
        $index = 0;
        foreach (str_split($letters) as $ch) {
            $index = $index * 26 + (ord($ch) - 64);
        }

        return $index - 1;
    }

    /**
     * @return array<int, string|int|float|bool>
     */
    private function readRow(XMLReader $reader): array
    {
        $cells = [];
        $position = -1;
        $depth = $reader->depth;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $depth) {
                break;
            }
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'c') {
                continue;
            }

            $ref = $reader->getAttribute('r');
            $position = $ref ? self::columnIndex($ref) : $position + 1;
            $type = $reader->getAttribute('t') ?? 'n';

            $value = $reader->isEmptyElement ? null : $this->readCell($reader, $type);
            if ($value !== null && $value !== '') {
                $cells[$position] = $value;
            }
        }

        return $cells;
    }

    private function readCell(XMLReader $reader, string $type): string|int|float|bool|null
    {
        $depth = $reader->depth;
        $raw = null;
        $inline = '';

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $depth) {
                break;
            }
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->localName === 'v') {
                $raw = $reader->readString();
            } elseif ($reader->localName === 't') {
                // <is><t> or rich text <is><r><t>: concatenate every run.
                $inline .= $reader->readString();
            }
        }

        return match ($type) {
            's' => $this->sharedString((int) $raw),
            'inlineStr' => $inline,
            'str', 'd' => $raw,
            'b' => $raw === '1',
            'e' => null, // #N/A, #REF! ... - treat as an empty cell.
            default => $this->number($raw),
        };
    }

    private function number(?string $raw): int|float|string|null
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return $raw;
        }
        $float = (float) $raw;
        // Whole numbers (ids, counts) come back as int, not "12.0".
        if (floor($float) === $float && abs($float) < PHP_INT_MAX) {
            return (int) $float;
        }

        return $float;
    }

    private function sharedString(int $index): string
    {
        if ($this->sharedStrings === null) {
            $this->sharedStrings = $this->loadSharedStrings();
        }

        return $this->sharedStrings[$index] ?? '';
    }

    /** @return list<string> */
    private function loadSharedStrings(): array
    {
        $path = $this->resolvePart('sharedStrings');
        if ($path === null) {
            return [];
        }

        $reader = $this->open($path);
        $strings = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                continue;
            }
            $depth = $reader->depth;
            $text = '';
            if (! $reader->isEmptyElement) {
                while ($reader->read()) {
                    if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $depth) {
                        break;
                    }
                    // Skip phonetic runs (<rPh>), which repeat the text in kana.
                    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'rPh') {
                        $reader->next();

                        continue;
                    }
                    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't') {
                        $text .= $reader->readString();
                    }
                }
            }
            $strings[] = $text;
        }
        $reader->close();

        return $strings;
    }

    /**
     * Worksheets in workbook order, with their part path inside the zip.
     *
     * @return list<array{name: string, path: string}>
     */
    private function sheets(): array
    {
        $targets = $this->relationshipTargets('xl/_rels/workbook.xml.rels', 'xl/');

        $reader = $this->open('xl/workbook.xml');
        $sheets = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'sheet') {
                continue;
            }
            $rid = $reader->getAttributeNs('id', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships')
                ?? $reader->getAttribute('r:id');
            $path = $targets[$rid] ?? null;
            if ($path !== null && $this->zip->locateName($path) !== false) {
                $sheets[] = ['name' => (string) $reader->getAttribute('name'), 'path' => $path];
            }
        }
        $reader->close();

        // Some generators omit the relationships part; fall back to the default name.
        if (! $sheets && $this->zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            $sheets[] = ['name' => 'Sheet1', 'path' => 'xl/worksheets/sheet1.xml'];
        }

        return $sheets;
    }

    /** Path of the workbook part with the given relationship type (e.g. sharedStrings). */
    private function resolvePart(string $type): ?string
    {
        $reader = $this->open('xl/_rels/workbook.xml.rels', optional: true);
        if ($reader === null) {
            return $this->zip->locateName('xl/sharedStrings.xml') !== false ? 'xl/sharedStrings.xml' : null;
        }

        $found = null;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'Relationship'
                && str_ends_with((string) $reader->getAttribute('Type'), '/'.$type)) {
                $found = $this->normalisePath('xl/', (string) $reader->getAttribute('Target'));
                break;
            }
        }
        $reader->close();

        return $found !== null && $this->zip->locateName($found) !== false ? $found : null;
    }

    /** @return array<string, string> relationship id → part path */
    private function relationshipTargets(string $relsPath, string $baseDir): array
    {
        $reader = $this->open($relsPath, optional: true);
        if ($reader === null) {
            return [];
        }

        $targets = [];
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'Relationship') {
                $targets[(string) $reader->getAttribute('Id')] = $this->normalisePath($baseDir, (string) $reader->getAttribute('Target'));
            }
        }
        $reader->close();

        return $targets;
    }

    /** Resolve a relationship target ("worksheets/sheet1.xml", "/xl/…", "../…") to a zip path. */
    private function normalisePath(string $baseDir, string $target): string
    {
        $path = str_starts_with($target, '/') ? ltrim($target, '/') : $baseDir.$target;

        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($parts);
            } elseif ($segment !== '' && $segment !== '.') {
                $parts[] = $segment;
            }
        }

        return implode('/', $parts);
    }

    /**
     * @return ($optional is true ? XMLReader|null : XMLReader)
     */
    private function open(string $part, bool $optional = false): ?XMLReader
    {
        $stat = $this->zip->statName($part);
        if ($stat === false) {
            if ($optional) {
                return null;
            }
            throw new XlsxException('Struktur file Excel tidak lengkap. Simpan ulang file sebagai .xlsx lalu coba lagi.');
        }
        if ($stat['size'] > self::MAX_PART_BYTES) {
            throw new XlsxException('File Excel terlalu besar untuk diproses.');
        }

        $xml = $this->zip->getFromName($part);
        if ($xml === false) {
            throw new XlsxException('File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).');
        }

        $reader = new XMLReader;
        // LIBXML_NONET: never fetch anything over the network. Entity
        // substitution (LIBXML_NOENT) is deliberately NOT enabled.
        if (! $reader->XML($xml, 'UTF-8', LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            throw new XlsxException('File Excel rusak atau tidak valid.');
        }

        return $reader;
    }
}
