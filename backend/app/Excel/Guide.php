<?php

namespace App\Excel;

/** Shared text for the "Petunjuk" sheet of every import template. */
final class Guide
{
    /** @return list<string> */
    public static function howTo(string $subject, string $sheetName): array
    {
        return [
            'Cara memakai template ini',
            '',
            "1. Isi mulai baris ke-2 pada lembar \"{$sheetName}\". Baris contoh boleh ditimpa atau dihapus.",
            "2. Kosongkan kolom ID untuk {$subject} baru.",
            '3. Isi kolom ID untuk memperbarui data yang sudah ada (ambil ID dari hasil "Unduh Excel").',
            '4. Sel yang dibiarkan kosong saat memperbarui berarti "biarkan seperti semula".',
            '',
        ];
    }
}
