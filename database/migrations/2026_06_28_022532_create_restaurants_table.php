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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('cuisine_type');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('phone')->nullable();
            $table->json('operating_hours')->nullable();
            $table->integer('delivery_fee')->default(0); // in cents
            $table->integer('estimated_delivery_time')->default(30); // in minutes
            $table->integer('estimated_pickup_time')->default(15); // in minutes
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('offers_delivery')->default(true);
            $table->boolean('offers_pickup')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
