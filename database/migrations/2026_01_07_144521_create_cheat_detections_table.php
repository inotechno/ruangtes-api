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
        Schema::create('cheat_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->onDelete('cascade');
            $table->string('detection_type'); // tab_switch, window_blur, keyboard_shortcut, etc.
            $table->json('detection_data')->nullable();
            $table->integer('severity')->default(1); // 1-5
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['test_session_id', 'detection_type']);
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheat_detections');
    }
};
