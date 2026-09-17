<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Questions submitted through the "Pusat Bantuan → Bertanya" tab.
     *
     * Kept separate from `problem_reports` even though the shape is similar:
     * the two are triaged differently in the dashboard, and a bug report has
     * no answer to write back.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            // How the admin can get back to the asker — there's no in-app inbox
            // and no mail service wired up, so a reply happens out of band.
            $table->string('contact')->nullable();
            $table->text('text');
            $table->text('answer')->nullable();
            $table->enum('status', ['baru', 'dijawab', 'ditutup'])->default('baru');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
