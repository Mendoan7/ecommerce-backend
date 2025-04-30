<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'));
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('address_id')->constrained()->onDelete('cascade');
            $table->string('courier');
            $table->string('courier_type');
            $table->string('courier_estimation');
            $table->decimal('courier_price', 10, 2);
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('voucher_value', 10, 2)->nullable();
            $table->decimal('voucher_cashback', 10, 2)->nullable();
            $table->decimal('service_fee', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('pay_with_coin', 10, 2);
            $table->string('payment_method');
            $table->decimal('total_payment', 10, 2);
            $table->timestamp('payment_expired_at')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('virtual_account_number')->nullable();
            $table->string('qris_image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
