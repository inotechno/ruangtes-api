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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payable_type'); // Invoice, PublicTransaction
            $table->unsignedBigInteger('payable_id');
            $table->string('payment_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('method')->nullable(); // manual, gateway (for future)
            $table->string('status'); // pending, paid, failed, refunded, cancelled
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index('payment_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
