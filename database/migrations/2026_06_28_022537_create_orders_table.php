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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')->default('received'); // received, preparing, ready_for_pickup, out_for_delivery, completed, cancelled
            $table->string('type'); // delivery, pickup
            $table->text('delivery_address')->nullable();
            $table->integer('subtotal'); // in cents
            $table->integer('delivery_fee'); // in cents
            $table->integer('service_fee')->default(200); // in cents
            $table->integer('tax'); // in cents
            $table->integer('discount')->default(0); // in cents
            $table->integer('total'); // in cents
            $table->text('special_instructions')->nullable();
            $table->timestamp('estimated_ready_time')->nullable();
            $table->string('payment_status')->default('pending'); // pending, paid, failed
            $table->string('payment_method')->default('mock'); // card, mock
            $table->string('promo_code')->nullable();
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
