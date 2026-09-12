<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-only visibility toggle, independent of the owner's `status`
     * (open/on leave/closed) and the approval `verification` state — lets
     * an admin temporarily unpublish an already-approved UMKM without
     * rejecting or deleting it.
     */
    public function up(): void
    {
        Schema::table('umkms', function (Blueprint $table) {
            $table->boolean('hidden')->default(false)->after('verification');
        });
    }

    public function down(): void
    {
        Schema::table('umkms', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }
};
