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
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_subscription_id')->constrained()->onDelete('cascade');
            $table->string('transaction_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status'); // pending, processing, completed, failed, cancelled
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('transaction_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};
