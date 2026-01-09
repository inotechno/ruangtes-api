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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('test_categories')->onDelete('set null');
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('question_count')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->string('type'); // public, company, all
            $table->text('description')->nullable();
            $table->string('instruction_route')->nullable();
            $table->string('test_route')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
