<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Social-media video slots shown in the "Video dari medsos NearBy" section
     * on the homepage. The rows are created up front (three slots, matching the
     * design) and an admin fills in `url` later from the dashboard - a slot with
     * a null `url` renders as the "Video belum tersedia" placeholder card.
     */
    public function up(): void
    {
        Schema::create('social_videos', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['youtube', 'instagram']);
            $table->string('title');
            $table->string('url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_videos');
    }
};
