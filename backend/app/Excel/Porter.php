<?php

namespace App\Excel;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Bulk export / import of one dataset for the dashboard's Excel feature.
 *
 * A subclass describes its sheet (columns, guide text) and three pieces of
 * behaviour - which rows a user may export, how one uploaded row is checked,
 * and how the checked rows are written. Everything else is shared here:
 *
 * Import is deliberately two-phase. `analyse()` validates every row and
 * reports what would happen without writing anything (the preview dialog);
 * `commit()` re-runs the same analysis from scratch and applies it inside one
 * transaction, refusing the whole sheet if any row is invalid. So a bad sheet
 * can never half-apply, and a tampered preview cannot skip a check.
 */
abstract class Porter
{
    /** Hard cap on data rows per import, for both JSON and .xlsx uploads. */
    public const MAX_ROWS = 2000;

    /**
     * Key carrying a row's real line number in the sheet. Set by the .xlsx
     * parser, which skips blank lines, so error messages still point at the
     * right line; JSON callers may omit it (then "index + 2" is assumed).
     */
    public const ROW_KEY = '_row';

    /** Worksheet tab name, e.g. "UMKM". */
    abstract public function sheetName(): string;

    /** Export filename, before the `-YYYY-MM-DD.xlsx` stamp. */
    abstract public function exportPrefix(): string;

    abstract public function templateFilename(): string;

    /** Key of the column that must appear in an uploaded sheet for it to make sense. */
    abstract public function requiredColumn(): string;

    /** @return list<Column> */
    abstract public function columns(): array;

    /**
     * Lines for the "Petunjuk" sheet of the template.
     *
     * @return list<string>
     */
    abstract public function guide(bool $isAdmin): array;

    /**
     * API-shaped rows the user is allowed to export.
     *
     * @return list<array<string, mixed>>
     */
    abstract public function exportRows(User $user): array;

    /**
     * Bulk-load whatever {@see inspect()} needs (existing rows, parents), so
     * checking a 2000-row sheet costs a couple of queries, not thousands.
     *
     * @param  list<array<string, mixed>>  $raws
     * @return array<string, mixed>
     */
    abstract protected function prepare(array $raws, User $user): array;

    /**
     * Check one uploaded row.
     *
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $context  Result of {@see prepare()}.
     * @return array{id: int|null, update: bool, name: string, messages: list<string>, data: array<string, mixed>}
     */
    abstract protected function inspect(array $raw, array $context, User $user): array;

    /**
     * Write rows that passed {@see inspect()}. Runs inside a transaction.
     *
     * @param  list<array{action: string, id: int|null, data: array<string, mixed>}>  $rows
     */
    abstract protected function apply(array $rows, User $user): void;

    /**
     * Columns present in this user's sheet.
     *
     * @return list<Column>
     */
    public function columnsFor(bool $isAdmin): array
    {
        return array_values(array_filter($this->columns(), fn (Column $c) => $isAdmin || ! $c->adminOnly));
    }

    /**
     * Validate a sheet and report what importing it would do. Writes nothing.
     *
     * @param  list<mixed>  $raws
     * @return array{summary: array{create: int, update: int, error: int}, rows: list<array<string, mixed>>}
     */
    public function analyse(array $raws, User $user): array
    {
        $raws = array_map(fn ($raw) => is_array($raw) ? $raw : [], array_values($raws));
        $context = $this->prepare($raws, $user);

        $rows = [];
        $summary = ['create' => 0, 'update' => 0, 'error' => 0];

        foreach ($raws as $i => $raw) {
            // Row 1 is the header in the sheet, so data starts at 2.
            $sheetRow = isset($raw[self::ROW_KEY]) && is_int($raw[self::ROW_KEY]) ? $raw[self::ROW_KEY] : $i + 2;
            $result = $this->inspect($raw, $context, $user);

            if ($result['messages']) {
                $summary['error']++;
                $rows[] = [
                    'row' => $sheetRow,
                    'action' => 'error',
                    'name' => $result['name'],
                    'messages' => $result['messages'],
                ];

                continue;
            }

            $action = $result['update'] ? 'update' : 'create';
            $summary[$action]++;
            $rows[] = [
                'row' => $sheetRow,
                'action' => $action,
                'id' => $result['id'],
                'name' => $result['name'],
                'messages' => [],
                'data' => $result['data'],
            ];
        }

        return ['summary' => $summary, 'rows' => $rows];
    }

    /**
     * Re-validate and apply. Nothing is written when any row is invalid.
     *
     * @param  list<mixed>  $raws
     * @return array{ok: bool, summary: array{create: int, update: int, error: int}, rows: list<array<string, mixed>>}
     */
    public function commit(array $raws, User $user): array
    {
        $result = $this->analyse($raws, $user);

        if ($result['summary']['error'] > 0) {
            return ['ok' => false, ...$result];
        }

        DB::transaction(fn () => $this->apply($result['rows'], $user));

        return ['ok' => true, ...$result];
    }

    /**
     * Collect the distinct integer ids found under `$key` across the rows.
     *
     * @param  list<array<string, mixed>>  $raws
     * @return list<int>
     */
    protected static function idsIn(array $raws, string $key): array
    {
        $ids = [];
        foreach ($raws as $raw) {
            if (isset($raw[$key]) && is_numeric($raw[$key])) {
                $ids[(int) $raw[$key]] = true;
            }
        }

        return array_keys($ids);
    }

    /** The integer under `$key`, or null when absent / not numeric. */
    protected static function intOrNull(array $raw, string $key): ?int
    {
        return isset($raw[$key]) && is_numeric($raw[$key]) ? (int) $raw[$key] : null;
    }
}
