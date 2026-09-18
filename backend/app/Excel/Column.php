<?php

namespace App\Excel;

/**
 * One column of an import/export sheet: an Indonesian header mapped to an API field.
 */
final class Column
{
    /** Read back as a number (ids). */
    public const NUMBER = 'number';

    /** Written as Ya/Tidak, read back as a real boolean. */
    public const BOOLEAN = 'boolean';

    /** Stored lowercase in the database, shown title-cased in the sheet. */
    public const TITLE_CASE = 'titleCase';

    public function __construct(
        public readonly string $header,
        public readonly string $key,
        public readonly float $width,
        public readonly ?string $type = null,
        /** Only present in an admin's sheet. */
        public readonly bool $adminOnly = false,
        /** Exported for context, but ignored when importing (derived figures). */
        public readonly bool $readOnly = false,
        /** Example value used in the downloadable template. */
        public readonly string|int|null $example = null,
    ) {}
}
