<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photos of a UMKM (storefront, menu, rooms ...).
     *
     * A row is either an uploaded file (`disk` + `path`, bytes under
     * `storage/app/public/umkm-photos`) or an external link pasted into the
     * Excel import (`url`). Exactly one of the two is set. The lowest
     * `sort_order` is the cover shown on cards and at the top of the detail page.
     */
    public function up(): void
    {
        Schema::create('umkm_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkms')->cascadeOnDelete();
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['umkm_id', 'sort_order'], 'umkm_photos_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_photos');
    }
};
