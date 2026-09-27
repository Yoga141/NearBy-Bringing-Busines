<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One review per user per UMKM, and ratings that come from real reviews.
     *
     *  1. Drop the demo reviews the old ReviewSeeder attached to every UMKM
     *     (no author account, fixed names and texts) and the demo submissions
     *     that pointed at no UMKM at all. Matched on their exact seeded content,
     *     so nothing a real user wrote is touched.
     *  2. Where one user already has several reviews on the same UMKM, keep the
     *     newest - the unique index below could not be created otherwise.
     *  3. Unique (umkm_id, user_id): the database itself refuses a second review,
     *     even from a request that skips the API's own check. Rows without a
     *     user (NULL) are not affected by the index.
     *  4. Recompute every cached rating / review count from the reviews that
     *     are actually stored (the seeded figures, e.g. 4.8 from 213 reviews,
     *     had only a handful of rows behind them).
     */
    public function up(): void
    {
        DB::table('reviews')
            ->whereNull('user_id')
            ->whereIn('author_name', ['Rani Oktaviani', 'Bayu Firmansyah', 'Siti Marlina'])
            ->where(function ($q) {
                $q->where('text', 'like', 'Pelayanannya ramah banget, rasanya juara!%')
                    ->orWhere('text', 'like', 'Tempatnya nyaman dan bersih. Harga sesuai kualitas%')
                    ->orWhere('text', 'like', 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy%');
            })
            ->delete();

        DB::table('submissions')
            ->whereNull('umkm_id')
            ->whereIn('name', ['Bakso Urat Cak War', 'Homestay Bukit Damai', 'Thrift Corner Senja'])
            ->delete();

        $duplicates = DB::table('reviews')
            ->select('umkm_id', 'user_id', DB::raw('max(id) as keep_id'))
            ->whereNotNull('user_id')
            ->groupBy('umkm_id', 'user_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('reviews')
                ->where('umkm_id', $dup->umkm_id)
                ->where('user_id', $dup->user_id)
                ->where('id', '<>', $dup->keep_id)
                ->delete();
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['umkm_id', 'user_id'], 'reviews_one_per_user_unique');
        });

        $stats = DB::table('reviews')
            ->select('umkm_id', DB::raw('count(*) as total'), DB::raw('avg(stars) as average'))
            ->groupBy('umkm_id')
            ->get()
            ->keyBy('umkm_id');

        foreach (DB::table('umkms')->pluck('id') as $id) {
            $row = $stats->get($id);
            DB::table('umkms')->where('id', $id)->update([
                'reviews_count' => $row ? (int) $row->total : 0,
                'rating' => $row ? round((float) $row->average, 1) : 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_one_per_user_unique');
        });
    }
};
