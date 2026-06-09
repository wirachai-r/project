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
        Schema::create('treatment_orders', function (Blueprint $table) {
            $table->char('order_id', 10)->primary();
            $table->string('order_name', 150);
            $table->string('order_name_en', 150)->nullable();
            $table->text('description')->nullable();
            $table->char('urgency_type', 1);  // R=Red, O=Orange, Y=Yellow, G=Green
            $table->integer('order_sequence')->default(0);
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('disease_id', 10);
            $table->foreign('disease_id')
                ->references('disease_id')
                ->on('diseases')
                ->restrictOnDelete();

            // FK
            $table->char('created_by', 9)->nullable();
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();

            // FK
            $table->char('updated_by', 9)->nullable();
            $table->foreign('updated_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('status');
            $table->index('disease_id');
            $table->index('urgency_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_orders');
    }
};
