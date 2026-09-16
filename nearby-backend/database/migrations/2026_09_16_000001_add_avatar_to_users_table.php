<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profile photo. Only the location on the `public` disk is stored — the
     * bytes live under `storage/app/public/avatars`, and `User::avatarUrl`
     * turns the path into the URL the frontend renders.
     *
     * Null means "no photo", which every avatar in the UI already handles by
     * falling back to the initial.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
