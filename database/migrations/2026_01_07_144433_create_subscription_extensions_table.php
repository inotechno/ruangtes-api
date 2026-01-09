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
        Schema::create('subscription_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_subscription_id')->constrained()->onDelete('cascade');
            $table->integer('extension_months');
            $table->decimal('amount', 10, 2);
            $table->timestamp('new_expires_at');
            $table->string('status'); // pending, completed, failed
            $table->timestamps();

            $table->index('company_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_extensions');
    }
};
