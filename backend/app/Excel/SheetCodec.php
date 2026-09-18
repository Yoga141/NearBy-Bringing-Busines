<?php

namespace App\Excel;

use App\Support\Xlsx\XlsxException;
use App\Support\Xlsx\XlsxReader;
use App\Support\Xlsx\XlsxWriter;

/**
 * Converts between a {@see Porter}'s API-shaped rows and .xlsx files.
 *
 * Every rule here matches what the dashboard's former in-browser (exceljs)
 * implementation did, so files exported by either version import cleanly.
 */
class SheetCodec
{
    private const TRUE_WORDS = ['ya', 'true', '1', 'tersedia', 'aktif', 'y'];

    private const FALSE_WORDS = ['tidak', 'false', '0', 'habis', 'nonaktif', 'n'];

    /**
     * Write rows to a temp .xlsx file and return its path.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function export(Porter $porter, array $rows, bool $isAdmin): string
    {
        $columns = $porter->columnsFor($isAdmin);

        $table = [array_map(fn (Column $c) => $c->header, $columns)];
        foreach ($rows as $row) {
            $table[] = array_map(fn (Column $c) => $this->toCell($c, $row[$c->key] ?? null), $columns);
        }

        return $this->writer($porter, $columns, $table)->saveToTemp();
    }

    /** An empty sheet with one example row, plus a "Petunjuk" (guide) sheet. */
    public function template(Porter $porter, bool $isAdmin): string
    {
        $columns = $porter->columnsFor($isAdmin);

        $table = [
            array_map(fn (Column $c) => $c->header, $columns),
            array_map(fn (Column $c) => $c->example, $columns),
        ];

        // The rules live on their own sheet, never under the data: a note placed
        // in the data sheet lands in the ID column and re-imports as a bogus row.
        $guide = array_map(fn (string $line) => [$line], $porter->guide($isAdmin));

        return $this->writer($porter, $columns, $table)
            ->addSheet('Petunjuk', [96], $guide, headerRow: false, titleRow: true)
            ->saveToTemp();
    }

    /**
     * Read an uploaded .xlsx into API-shaped rows.
     *
     * Headers are matched case-insensitively and ignoring surrounding spaces,
     * so a lightly reformatted sheet still imports. Read-only columns are
     * dropped here rather than being sent on and rejected. Each row carries
     * its real sheet line under {@see Porter::ROW_KEY}.
     *
     * @return list<array<string, mixed>>
     *
     * @throws XlsxException with a message meant for the user.
     */
    public function parse(Porter $porter, string $path, bool $isAdmin): array
    {
        $reader = new XlsxReader($path);
        try {
            $sheet = $reader->rows(0, Porter::MAX_ROWS);
        } finally {
            $reader->close();
        }

        $byHeader = [];
        foreach ($porter->columnsFor($isAdmin) as $column) {
            if (! $column->readOnly) {
                $byHeader[$this->normaliseHeader($column->header)] = $column;
            }
        }

        // Map each sheet column index to a field, from the header row.
        $indexToColumn = [];
        foreach ($sheet[1] ?? [] as $index => $label) {
            $column = $byHeader[$this->normaliseHeader((string) $label)] ?? null;
            if ($column) {
                $indexToColumn[$index] = $column;
            }
        }

        if (! $indexToColumn) {
            throw new XlsxException('Baris pertama tidak dikenali sebagai judul kolom. Gunakan tombol "Unduh template" sebagai acuan.');
        }

        $required = $porter->requiredColumn();
        if (! in_array($required, array_map(fn (Column $c) => $c->key, $indexToColumn), true)) {
            $header = collect($porter->columns())->firstWhere('key', $required)?->header ?? $required;
            throw new XlsxException("Kolom \"{$header}\" tidak ditemukan di file ini.");
        }

        $rows = [];
        foreach ($sheet as $rowNumber => $cells) {
            if ($rowNumber === 1) {
                continue;
            }

            $parsed = [Porter::ROW_KEY => $rowNumber];
            $hasValue = false;
            foreach ($indexToColumn as $index => $column) {
                $value = $this->fromCell($column, $cells[$index] ?? null);
                $hasValue = $hasValue || $value !== null;
                $parsed[$column->key] = $value;
            }

            // Skip rows that only have values in ignored columns.
            if ($hasValue) {
                $rows[] = $parsed;
            }
        }

        if (! $rows) {
            throw new XlsxException('Tidak ada baris data di file ini.');
        }

        return $rows;
    }

    /** "Export filename-2026-09-18.xlsx" */
    public function exportFilename(Porter $porter): string
    {
        return $porter->exportPrefix().'-'.now()->format('Y-m-d').'.xlsx';
    }

    /**
     * @param  list<Column>  $columns
     * @param  list<list<mixed>>  $table
     */
    private function writer(Porter $porter, array $columns, array $table): XlsxWriter
    {
        return (new XlsxWriter)->addSheet(
            $porter->sheetName(),
            array_map(fn (Column $c) => $c->width, $columns),
            $table,
        );
    }

    private function toCell(Column $column, mixed $value): string|int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($column->type) {
            Column::BOOLEAN => $value ? 'Ya' : 'Tidak',
            Column::TITLE_CASE => mb_convert_case((string) $value, MB_CASE_TITLE),
            default => is_int($value) || is_float($value) ? $value : (string) $value,
        };
    }

    private function fromCell(Column $column, mixed $value): string|int|float|bool|null
    {
        if ($value === null) {
            return null;
        }

        if ($column->type === Column::BOOLEAN && is_bool($value)) {
            return $value;
        }

        $text = trim(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        if ($text === '') {
            return null;
        }

        return match ($column->type) {
            Column::BOOLEAN => $this->toBoolean($text),
            Column::NUMBER => is_numeric($text) ? $text + 0 : $text,
            Column::TITLE_CASE => mb_strtolower($text),
            // Everything else is text - a phone typed as a number included.
            default => $text,
        };
    }

    /** Ya/Tidak-style words to a boolean; anything else goes on unchanged so validation can name it. */
    private function toBoolean(string $text): bool|string
    {
        $word = mb_strtolower($text);

        return match (true) {
            in_array($word, self::TRUE_WORDS, true) => true,
            in_array($word, self::FALSE_WORDS, true) => false,
            default => $text,
        };
    }

    private function normaliseHeader(string $label): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $label) ?? $label));
    }
}
