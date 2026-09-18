<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registration tutorial videos an admin uploads for the "Panduan" page.
     *
     * Unlike `social_videos`, which only stores a link to someone else's player,
     * these are files we host ourselves: the row carries the location on disk
     * and the metadata needed to serve it back (`mime_type`, `size`), while the
     * bytes live under `storage/app/public/guide-videos`.
     *
     * Several videos may be uploaded, but the guide page shows one - the newest
     * row with `active` set. Keeping the older ones lets an admin swap back
     * without re-uploading.
     */
    public function up(): void
    {
        Schema::create('guide_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            /** The admin's own filename, shown in the dashboard list. */
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->boolean('active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_videos');
    }
};
