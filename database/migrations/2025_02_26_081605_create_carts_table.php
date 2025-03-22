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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('(UUID())'));
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('address_id')->nullable();
            $table->string('courier')->nullable();
            $table->string('courier_type')->nullable();
            $table->string('courier_estimation')->nullable();
            $table->string('courier_price', 12, 2)->default(0);
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->decimal('voucher_value', 12, 2)->nullable();
            $table->decimal('voucher_cashback', 12, 2)->nullable();
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('pay_with_coin', 12, 2)->default(0);
            $table->string('payment_method', 12, 2)->nullable();
            $table->decimal('total_payment', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('address_id')->references('id')->on('addresses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
