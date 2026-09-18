<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the queries that run on every page view.
     *
     *  - umkms (verification, hidden, rating): the public catalogue filters on
     *    the first two and sorts by the third (UmkmController::index).
     *  - umkms (owner_id, deleted_at): the owner dashboard and its trash tab.
     *    SQLite does not index foreign keys on its own.
     *  - reviews (umkm_id, created_at): a UMKM's reviews, newest first.
     *  - reviews (user_id): "Riwayat → Komentar" in the account page.
     *  - submissions (status, created_at): the admin verification queue.
     *  - problem_reports / questions (created_at): admin lists, newest first.
     */
    public function up(): void
    {
        Schema::table('umkms', function (Blueprint $table) {
            $table->index(['verification', 'hidden', 'rating'], 'umkms_public_listing_index');
            $table->index(['owner_id', 'deleted_at'], 'umkms_owner_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['umkm_id', 'created_at'], 'reviews_umkm_latest_index');
            $table->index('user_id', 'reviews_user_index');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'submissions_queue_index');
        });

        Schema::table('problem_reports', function (Blueprint $table) {
            $table->index('created_at', 'problem_reports_latest_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index('created_at', 'questions_latest_index');
        });
    }

    public function down(): void
    {
        Schema::table('questions', fn (Blueprint $table) => $table->dropIndex('questions_latest_index'));
        Schema::table('problem_reports', fn (Blueprint $table) => $table->dropIndex('problem_reports_latest_index'));
        Schema::table('submissions', fn (Blueprint $table) => $table->dropIndex('submissions_queue_index'));

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_user_index');
            $table->dropIndex('reviews_umkm_latest_index');
        });

        Schema::table('umkms', function (Blueprint $table) {
            $table->dropIndex('umkms_owner_index');
            $table->dropIndex('umkms_public_listing_index');
        });
    }
};
