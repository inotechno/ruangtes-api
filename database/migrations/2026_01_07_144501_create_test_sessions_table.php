<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('testable_type'); // Participant, User (PublicUser)
            $table->unsignedBigInteger('testable_id');
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_assignment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_token')->unique();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->default(0);
            $table->string('status'); // in_progress, completed, abandoned, banned
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['testable_type', 'testable_id']);
            $table->index('session_token');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_sessions');
    }
};
