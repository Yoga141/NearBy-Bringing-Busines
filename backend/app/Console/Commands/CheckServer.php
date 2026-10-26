<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Pre-flight check for the production server: PHP extensions, the storage
 * symlink, writable folders, upload limits and APP_URL. Prints OK/GAGAL per
 * item and exits non-zero when anything required fails.
 *
 *   php artisan app:check-server
 *   php artisan app:check-server --public=/home/nearby/public_html
 */
class CheckServer extends Command
{
    protected $signature = 'app:check-server
        {--public= : Folder yang disajikan web server (berisi symlink storage). Default: ~/public_html bila ada, selain itu backend/public}';

    protected $description = 'Cek kesiapan server: ekstensi PHP, symlink storage, izin folder, batas upload, APP_URL';

    /** Per photo, matching the upload controllers. */
    private const PHOTO_BYTES = 2 * 1024 * 1024;

    private int $failures = 0;

    public function handle(): int
    {
        $this->line('PHP '.PHP_VERSION.' ('.PHP_SAPI.', php.ini: '.(php_ini_loaded_file() ?: 'tidak ada').')');
        $this->line('Catatan: nilai php.ini di CLI bisa berbeda dengan PHP web (PHP-FPM/LSAPI). Pastikan versi PHP yang sama dipilih untuk keduanya.');
        $this->newLine();

        $this->components->info('Ekstensi PHP');
        foreach (['fileinfo', 'pdo_mysql', 'mbstring', 'openssl'] as $ext) {
            $this->check("ekstensi $ext", extension_loaded($ext), extension_loaded($ext) ? 'aktif' : 'belum aktif');
        }
        $image = extension_loaded('gd') || extension_loaded('imagick');
        $this->check('ekstensi gd / imagick (opsional)', true, $image
            ? 'aktif'
            : 'tidak aktif - tidak dipakai aplikasi saat ini (foto disimpan apa adanya), tidak wajib');

        $this->components->info('Storage');
        $public = $this->publicDir();
        $link = $public.DIRECTORY_SEPARATOR.'storage';
        $target = storage_path('app/public');
        $linked = is_link($link) || is_dir($link);
        $sameTarget = $linked && realpath($link) === realpath($target);
        $this->check('symlink '.$link, $sameTarget, match (true) {
            $sameTarget => '-> '.$target,
            $linked => 'ada, tapi menunjuk ke '.(realpath($link) ?: '?').' (seharusnya '.$target.')',
            default => 'belum ada. Buat: ln -sfn '.$target.' '.$link,
        });

        foreach ([storage_path(), storage_path('app/public'), storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $dir) {
            $this->check('bisa ditulis: '.$dir, is_dir($dir) && is_writable($dir), is_dir($dir)
                ? (is_writable($dir) ? 'OK' : 'tidak bisa ditulis PHP. Jalankan: chmod -R 775 '.$dir)
                : 'folder tidak ada. Jalankan: mkdir -p '.$dir);
        }

        $this->components->info('Batas upload');
        $upload = $this->bytes((string) ini_get('upload_max_filesize'));
        $post = $this->bytes((string) ini_get('post_max_size'));
        $this->check('upload_max_filesize = '.ini_get('upload_max_filesize'), $upload >= self::PHOTO_BYTES,
            $upload >= self::PHOTO_BYTES ? 'cukup untuk foto 2 MB' : 'kurang dari 2 MB; foto yang sah akan ditolak');
        $this->check('post_max_size = '.ini_get('post_max_size'), $post === 0 || $post > $upload,
            $post === 0 || $post > $upload ? 'lebih besar dari upload_max_filesize' : 'harus lebih besar dari upload_max_filesize (beberapa foto sekaligus)');
        $this->check('file_uploads', (bool) ini_get('file_uploads'), ini_get('file_uploads') ? 'On' : 'Off - upload tidak mungkin');

        $this->components->info('Aplikasi');
        $url = (string) config('app.url');
        // localhost is fine on a dev machine, never on the server.
        $urlOk = filter_var($url, FILTER_VALIDATE_URL)
            && ! (app()->isProduction() && (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')));
        $this->check('APP_URL = '.($url ?: '(kosong)'), (bool) $urlOk, $urlOk
            ? 'OK'
            : 'isi dengan domain asli, mis. https://www.nearbybalikpapan.com');
        $https = str_starts_with($url, 'https://');
        $this->check('APP_URL memakai https', true, $https ? 'ya' : 'tidak (disarankan https di produksi)');

        $this->newLine();
        if ($this->failures) {
            $this->components->error($this->failures.' item GAGAL. Perbaiki lalu jalankan ulang perintah ini.');

            return self::FAILURE;
        }
        $this->components->info('Semua pemeriksaan wajib OK.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $ok, string $detail): void
    {
        if (! $ok) {
            $this->failures++;
        }
        $this->line(sprintf('  [%s] %s - %s', $ok ? '<fg=green>OK</>' : '<fg=red>GAGAL</>', $label, $detail));
    }

    /** --public, else ~/public_html (the cPanel layout), else backend/public. */
    private function publicDir(): string
    {
        if ($dir = $this->option('public')) {
            return rtrim($dir, '/\\');
        }
        $home = getenv('HOME') ?: dirname(base_path());
        foreach ([$home.'/public_html', dirname(base_path()).'/public_html'] as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return public_path();
    }

    /** php.ini shorthand ("2M", "512K", "1G") to bytes. */
    private function bytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
