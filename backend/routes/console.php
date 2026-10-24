<?php

use App\Http\Controllers\Api\ProductPhotoController;
use App\Http\Controllers\Api\UmkmPhotoController;
use App\Models\UmkmItem;
use App\Models\UmkmPhoto;
use App\Support\ImageOptimizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove API tokens that expired more than a day ago (see config/sanctum.php).
// Needs the scheduler: a cron entry running `php artisan schedule:run` every minute.
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Shrink photos uploaded before uploads were optimised: re-encode to WebP at
// the same max edge new uploads get. Safe to re-run; already-WebP files are skipped.
Artisan::command('photos:optimize {--dry-run : Only report what would change}', function () {
    $dry = (bool) $this->option('dry-run');
    $saved = 0;

    $convert = function (string $disk, string $path, int $maxEdge) use ($dry, &$saved): ?string {
        $storage = Storage::disk($disk);
        if (str_ends_with(strtolower($path), '.webp') || ! $storage->exists($path)) {
            return null;
        }
        $before = $storage->size($path);
        $bytes = ImageOptimizer::toWebp($storage->path($path), $maxEdge, $before);
        if ($bytes === null) {
            return null;
        }

        $saved += $before - strlen($bytes);
        $this->line(sprintf('%s: %d KB -> %d KB', $path, $before / 1024, strlen($bytes) / 1024));
        if ($dry) {
            return null;
        }

        $newPath = dirname($path).'/'.Str::random(40).'.webp';

        return $storage->put($newPath, $bytes) ? $newPath : null;
    };

    foreach (UmkmPhoto::whereNotNull('path')->whereNotNull('disk')->get() as $photo) {
        if (config("filesystems.disks.{$photo->disk}.driver") !== 'local') {
            continue;
        }
        if ($newPath = $convert($photo->disk, $photo->path, UmkmPhotoController::MAX_EDGE)) {
            $old = $photo->path;
            $photo->update(['path' => $newPath]);
            Storage::disk($photo->disk)->delete($old);
        }
    }

    foreach (UmkmItem::whereNotNull('img')->distinct()->pluck('img') as $url) {
        $path = UmkmItem::managedPhotoPath($url);
        if ($path && ($newPath = $convert('public', $path, ProductPhotoController::MAX_EDGE))) {
            UmkmItem::where('img', $url)->update(['img' => '/api/product-photos/'.basename($newPath)]);
            Storage::disk('public')->delete($path);
        }
    }

    $this->info(sprintf('%s %d KB.', $dry ? 'Bisa dihemat' : 'Dihemat', $saved / 1024));
})->purpose('Kompres foto UMKM & produk lama ke WebP');
